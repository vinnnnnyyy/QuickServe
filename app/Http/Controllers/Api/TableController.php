<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\JsonStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TableController extends Controller
{
    private JsonStorageService $storage;

    public function __construct(JsonStorageService $storage)
    {
        $this->storage = $storage;
        $this->initializeSampleData();
    }

    private function initializeSampleData(): void
    {
        $tables = $this->storage->get('tables');
        
        if ($tables->isEmpty()) {
            $sampleTables = [];
            for ($i = 1; $i <= 15; $i++) {
                $sampleTables[] = [
                    'id' => $i,
                    'number' => $i,
                    'seats' => rand(2, 6),
                    'status' => $i <= 3 ? 'occupied' : 'available',
                    'location' => $i <= 8 ? 'Indoor' : 'Outdoor',
                    'notes' => $i === 1 ? 'VIP table' : '',
                    'created_at' => now()->toISOString(),
                    'updated_at' => now()->toISOString()
                ];
            }

            $this->storage->put('tables', collect($sampleTables));
        }
    }

    public function index(): JsonResponse
    {
        $tables = $this->storage->get('tables');
        return response()->json($tables);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'number' => 'required|integer',
            'seats' => 'required|integer|min:1|max:12',
            'location' => 'required|string',
            'notes' => 'nullable|string'
        ]);

        $data = $request->all();
        $data['status'] = 'available';

        $table = $this->storage->create('tables', $data);
        
        return response()->json($table, 201);
    }

    public function show(int $id): JsonResponse
    {
        $table = $this->storage->find('tables', $id);
        
        if (!$table) {
            return response()->json(['message' => 'Table not found'], 404);
        }
        
        return response()->json($table);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $table = $this->storage->update('tables', $id, $request->all());
        
        if (!$table) {
            return response()->json(['message' => 'Table not found'], 404);
        }
        
        return response()->json($table);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:available,occupied,reserved,maintenance'
        ]);

        $table = $this->storage->update('tables', $id, ['status' => $request->status]);
        
        if (!$table) {
            return response()->json(['message' => 'Table not found'], 404);
        }
        
        return response()->json($table);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->storage->delete('tables', $id);
        
        if (!$deleted) {
            return response()->json(['message' => 'Table not found'], 404);
        }
        
        return response()->json(['message' => 'Table deleted successfully']);
    }
}
