<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Table;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TableController extends Controller
{
    public function index(): JsonResponse
    {
        $tables = Table::all();
        return response()->json($tables);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'number' => 'required|integer',
            'capacity' => 'required|integer|min:1|max:20',
            'location' => 'required|in:Indoor,Outdoor,Patio,Bar',
            'description' => 'nullable|string'
        ]);

        $data = $request->only(['number', 'capacity', 'location', 'description']);
        $data['status'] = 'available';
        $data['occupied'] = 0;
        $data['qr_code'] = 'QR_TABLE_' . str_pad($data['number'], 3, '0', STR_PAD_LEFT);

        $table = Table::create($data);
        
        $serverIp = $request->ip() === '127.0.0.1' || $request->ip() === '::1' 
            ? gethostbyname(gethostname()) 
            : $request->server('SERVER_ADDR');
        
        $table->qr_code_url = 'http://' . $serverIp . ':' . $request->server('SERVER_PORT', '8000') . '/table/' . $table->id;
        $table->save();
        
        return response()->json($table, 201);
    }

    public function show(int $id): JsonResponse
    {
        $table = Table::find($id);
        
        if (!$table) {
            return response()->json(['message' => 'Table not found'], 404);
        }
        
        return response()->json($table);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $table = Table::find($id);
        
        if (!$table) {
            return response()->json(['message' => 'Table not found'], 404);
        }

        $table->update($request->all());

        return response()->json($table);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:available,partial,full,cleaning,reserved,out_of_service'
        ]);

        $table = Table::find($id);
        
        if (!$table) {
            return response()->json(['message' => 'Table not found'], 404);
        }

        $table->update([
            'status' => $request->status,
            'status_changed_at' => now(),
        ]);
        
        return response()->json($table);
    }

    public function destroy(int $id): JsonResponse
    {
        $table = Table::find($id);
        
        if (!$table) {
            return response()->json(['message' => 'Table not found'], 404);
        }

        $table->delete();

        return response()->json(['message' => 'Table deleted successfully']);
    }
}
