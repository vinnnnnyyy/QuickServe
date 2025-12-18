<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Order;
use App\Services\DeviceIdentifierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TableCartController extends Controller
{
    /**
     * Get all cart items for the current table.
     */
    public function index(Request $request): JsonResponse
    {
        $tableId = session('table_id');
        
        if (!$tableId) {
            return response()->json(['message' => 'Table context required'], 403);
        }

        // Fetch session to get user names map
        $session = \App\Models\TableSession::where('table_id', $tableId)
            ->whereIn('status', ['active', 'paid_leaving'])
            ->first();
            
        $userMap = [];
        if ($session && $session->users) {
            foreach ($session->users as $user) {
                $userMap[$user['device_id']] = $user['name'];
            }
        }

        // Check payment mode from session metadata
        $paymentMode = $session->metadata['payment_mode'] ?? 'host';
        $deviceId = $request->attributes->get('device_id') ?? session('device_id');

        \Illuminate\Support\Facades\Log::info("Cart Debug: Mode [$paymentMode] Device [$deviceId] Table [$tableId]");
        
        $query = CartItem::with('product')->where('table_id', $tableId);

        // SEPARATION LOGIC:
        // 1. Individual Mode: Show ONLY my own items
        if ($paymentMode === 'individual') {
            $query->where('device_id', $request->attributes->get('device_id') ?? session('device_id'));
        } 
        // 2. Group/Host Mode: Show ALL items for this table (Shared Cart)
        // We rely on 'table_id' scope above. Logic: If you are in a group session on this table, you see everything.
        else {
             // No further device_id filtering needed.
        }

        $items = $query->get()
            ->map(function ($item) use ($userMap) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'name' => $item->product ? $item->product->name : 'Unknown Item',
                    'price' => $item->product ? ($item->product->price / 100) : 0, // Convert cents to base unit for frontend
                    'quantity' => $item->quantity,
                    'options' => $item->options,
                    'notes' => $item->notes,
                    'added_by_me' => $item->device_id === (request()->attributes->get('device_id') ?? session('device_id')),
                    'added_by_name' => $userMap[$item->device_id] ?? 'Guest',
                    'device_id' => $item->device_id,
                ];
            });

        return response()->json($items);
    }

    /**
     * Add an item to the table cart.
     */
    public function store(Request $request): JsonResponse
    {
        $tableId = session('table_id');
        $deviceId = $request->attributes->get('device_id') ?? session('device_id'); // Middleware sets this

        if (!$tableId || !$deviceId) {
            return response()->json(['message' => 'Table context required'], 403);
        }

        // Verify session is active (basic check)
        $session = \App\Models\TableSession::where('table_id', $tableId)
            ->where('status', 'active')
            ->first();

        // Relaxed Check: Allow adding items if session exists, even if status is pending.
        // This ensures the "auto-join" flow is frictionless.
        if (!$session) {
             return response()->json(['message' => 'No active session.'], 403);
        }

        $validated = $request->validate([
            'id' => 'required|integer|exists:menu_items,id', // product id
            'quantity' => 'required|integer|min:1',
            'options' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        $cartItem = CartItem::create([
            'table_id' => $tableId,
            'product_id' => $validated['id'],
            'device_id' => $deviceId,
            'quantity' => $validated['quantity'],
            'options' => $validated['options'] ?? [],
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json($cartItem, 201);
    }

    /**
     * Remove an item from the cart.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $tableId = session('table_id');
        $deviceId = $request->attributes->get('device_id') ?? session('device_id');
        
        if (!$tableId) {
            return response()->json(['message' => 'Table context required'], 403);
        }

        $cartItem = CartItem::where('table_id', $tableId)->where('id', $id)->first();

        if (!$cartItem) {
            return response()->json(['message' => 'Item not found in table cart'], 404);
        }

        // Permission Check: 
        // 1. Own item -> Allow
        // 2. Host -> Allow deleting Any item
        
        $session = \App\Models\TableSession::where('table_id', $tableId)
            ->where('status', 'active')
            ->first();
            
        $isHost = $session && $session->host_device_id === $deviceId;
        $isMine = $cartItem->device_id === $deviceId;

        if (!$isMine && !$isHost) {
            return response()->json(['message' => 'You can only remove your own items.'], 403);
        }

        $cartItem->delete();

        return response()->json(['message' => 'Item removed']);
    }

    /**
     * Checkout: Convert table cart to a single order.
     */
    public function checkout(Request $request): JsonResponse
    {
        $tableId = session('table_id');
        $deviceId = $request->attributes->get('device_id') ?? session('device_id');

        if (!$tableId) {
            return response()->json(['message' => 'Table context required'], 403);
        }

        $validated = $request->validate([
            'customer_name' => 'nullable|string',
            'payment_method' => 'required|string',
        ]);

        // Start transaction
        return DB::transaction(function () use ($tableId, $deviceId, $validated, $request) {
            $query = CartItem::with('product')->where('table_id', $tableId);
            
            // If individual payment mode (KKB), only checkout own items
            $isIndividual = $request->input('mode') === 'individual';
            
            if ($isIndividual) {
                $query->where('device_id', $deviceId);
            }

            $cartItems = $query->get();

            if ($cartItems->isEmpty()) {
                return response()->json(['message' => 'No items to checkout for this device'], 400);
            }

            // Calculate totals
            $items = $cartItems->map(function ($item) {
                // Determine price (base + options)
                $price = $item->product->price; // Keep as integer (cents) for DB storage reliability
                
                return [
                    'id' => $item->product_id,
                    'name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'price' => $price,
                    'options' => $item->options,
                    'notes' => $item->notes,
                ];
            })->toArray();

            $total = collect($items)->sum(function ($item) {
                return $item['price'] * $item['quantity'];
            });

            // Create Order
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'table_id' => $tableId,
                'table_number' => session('table_number') ?? 'Unknown',
                'customer_nickname' => $validated['customer_name'] ?? ($isIndividual ? 'Individual Order' : 'Table Order'),
                'items' => $items,
                'total' => $total, // Already in Cents, do NOT multiply by 100 again
                'subtotal' => $total,
                'status' => 'received',
                'payment_status' => $validated['payment_method'] === 'cash' ? 'unpaid' : 'pending',
                'payment_method' => $validated['payment_method'],
                'order_type' => 'dine_in',
                'device_id' => $deviceId, 
                'session_id' => session()->getId(),
                'device_ip' => $request->ip(),
                'device_type' => $request->header('User-Agent'),
                'original_data' => [
                    'is_group_order' => true,
                    'payment_mode' => $isIndividual ? 'split' : 'host',
                    'is_table_cart' => true
                ]
            ]);

            // Clear Cart - ONLY delete items that were ordered
            $cartItemIds = $cartItems->pluck('id');
            CartItem::whereIn('id', $cartItemIds)->delete();

            return response()->json($order, 201);
        });
    }
}
