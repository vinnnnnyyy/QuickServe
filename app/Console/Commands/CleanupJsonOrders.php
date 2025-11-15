<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\JsonStorageService;
use Illuminate\Console\Command;

class CleanupJsonOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:cleanup-json {--force : Skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete JSON-stored orders that are not present in the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $storage = app(JsonStorageService::class);
        
        $this->info('🔍 Checking for JSON-stored orders...');
        
        // Get all JSON orders
        $jsonOrders = $storage->get('orders');
        
        if ($jsonOrders->isEmpty()) {
            $this->info('✅ No orders found in JSON storage.');
            return 0;
        }
        
        $this->info("📦 Found {$jsonOrders->count()} orders in JSON storage.");
        
        // Get all database order IDs and order numbers
        $dbOrderIds = Order::pluck('id')->toArray();
        $dbOrderNumbers = Order::pluck('order_number')->toArray();
        
        $this->info("💾 Found " . count($dbOrderIds) . " orders in database.");
        
        // Find JSON orders that don't exist in database
        // Check both by ID and order_number
        $ordersToDelete = $jsonOrders->filter(function ($order) use ($dbOrderIds, $dbOrderNumbers) {
            $hasDbId = isset($order['id']) && in_array($order['id'], $dbOrderIds);
            $hasOrderNumber = isset($order['order_number']) && in_array($order['order_number'], $dbOrderNumbers);
            
            // Delete if order doesn't exist in DB by either ID or order_number
            return !$hasDbId && !$hasOrderNumber;
        });
        
        if ($ordersToDelete->isEmpty()) {
            $this->info('✅ All JSON orders exist in the database. Nothing to delete.');
            return 0;
        }
        
        // Display orders to be deleted
        $this->warn("\n⚠️  Found {$ordersToDelete->count()} orders in JSON that are NOT in database:");
        
        $this->table(
            ['ID', 'Order Number', 'Customer', 'Total', 'Status', 'Created At'],
            $ordersToDelete->map(function ($order) {
                return [
                    $order['id'] ?? 'N/A',
                    $order['order_number'] ?? 'N/A',
                    $order['customer_nickname'] ?? $order['customer_name'] ?? 'Unknown',
                    '₱' . number_format($order['total'] ?? 0, 2),
                    $order['status'] ?? 'N/A',
                    $order['created_at'] ?? 'N/A'
                ];
            })->toArray()
        );
        
        // Confirmation
        if (!$this->option('force')) {
            if (!$this->confirm('\nDo you want to delete these orders from JSON storage?', false)) {
                $this->info('❌ Operation cancelled.');
                return 1;
            }
        }
        
        // Keep only orders that exist in database
        $ordersToKeep = $jsonOrders->filter(function ($order) use ($dbOrderIds, $dbOrderNumbers) {
            $hasDbId = isset($order['id']) && in_array($order['id'], $dbOrderIds);
            $hasOrderNumber = isset($order['order_number']) && in_array($order['order_number'], $dbOrderNumbers);
            
            return $hasDbId || $hasOrderNumber;
        });
        
        // Save the filtered orders back to JSON
        $storage->put('orders', $ordersToKeep);
        
        $this->info("\n✅ Successfully deleted {$ordersToDelete->count()} orders from JSON storage.");
        $this->info("📊 Remaining JSON orders: {$ordersToKeep->count()}");
        
        return 0;
    }
}
