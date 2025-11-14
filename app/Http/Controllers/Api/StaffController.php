<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\JsonStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StaffController extends Controller
{
    private JsonStorageService $storage;

    public function __construct(JsonStorageService $storage)
    {
        $this->storage = $storage;
        $this->initializeSampleData();
    }

    private function initializeSampleData(): void
    {
        $staff = $this->storage->get('staff');
        
        if ($staff->isEmpty()) {
            $sampleStaff = [
                [
                    'id' => 1,
                    'name' => 'Alice Johnson',
                    'email' => 'alice@quickserve.com',
                    'phone' => '555-0123',
                    'role' => 'Manager',
                    'shift' => 'Morning',
                    'hourly_rate' => 25.00,
                    'status' => 'active',
                    'hire_date' => '2024-01-15',
                    'created_at' => now()->toISOString(),
                    'updated_at' => now()->toISOString()
                ],
                [
                    'id' => 2,
                    'name' => 'Bob Wilson',
                    'email' => 'bob@quickserve.com',
                    'phone' => '555-0124',
                    'role' => 'Barista',
                    'shift' => 'Morning',
                    'hourly_rate' => 18.00,
                    'status' => 'active',
                    'hire_date' => '2024-02-01',
                    'created_at' => now()->toISOString(),
                    'updated_at' => now()->toISOString()
                ]
            ];

            $this->storage->put('staff', collect($sampleStaff));
        }
    }

    public function index(): JsonResponse
    {
        $staff = $this->storage->get('staff');
        return response()->json($staff);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|string',
            'shift' => 'required|string',
            'hourly_rate' => 'required|numeric|min:0',
            'hire_date' => 'required|date'
        ]);

        $data = $request->all();
        $data['status'] = 'active';

        $staff = $this->storage->create('staff', $data);
        
        return response()->json($staff, 201);
    }

    public function show(int $id): JsonResponse
    {
        $staff = $this->storage->find('staff', $id);
        
        if (!$staff) {
            return response()->json(['message' => 'Staff member not found'], 404);
        }
        
        return response()->json($staff);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $staff = $this->storage->update('staff', $id, $request->all());
        
        if (!$staff) {
            return response()->json(['message' => 'Staff member not found'], 404);
        }
        
        return response()->json($staff);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->storage->delete('staff', $id);
        
        if (!$deleted) {
            return response()->json(['message' => 'Staff member not found'], 404);
        }
        
        return response()->json(['message' => 'Staff member deleted successfully']);
    }
}
