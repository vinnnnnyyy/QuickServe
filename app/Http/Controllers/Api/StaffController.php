<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class StaffController extends Controller
{
    /**
     * Transform Staff model with User to the expected JSON format.
     */
    private function transformStaff(Staff $staff): array
    {
        return [
            'id' => $staff->id,
            'name' => $staff->user->name,
            'email' => $staff->user->email,
            'phone' => $staff->phone,
            'role' => $staff->role,
            'shift' => $staff->shift,
            'hourly_rate' => (float) $staff->hourly_rate,
            'status' => $staff->status,
            'hire_date' => $staff->hire_date->format('Y-m-d'),
            'image' => $staff->image ? Storage::url($staff->image) : null,
            'created_at' => $staff->created_at->toISOString(),
            'updated_at' => $staff->updated_at->toISOString(),
        ];
    }

    public function index(): JsonResponse
    {
        $staff = Staff::with('user')->get();
        $transformed = $staff->map(fn($s) => $this->transformStaff($s));
        return response()->json($transformed);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|string',
            'shift' => 'required|string',
            'hourly_rate' => 'required|numeric|min:0',
            'hire_date' => 'required|date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
        ]);

        try {
            DB::beginTransaction();

            // Handle image upload
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('staff/images', 'public');
            }

            // Create user account
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            // Create staff profile
            $staff = Staff::create([
                'user_id' => $user->id,
                'image' => $imagePath,
                'phone' => $validated['phone'] ?? null,
                'role' => $validated['role'],
                'shift' => $validated['shift'],
                'hourly_rate' => $validated['hourly_rate'],
                'status' => 'active',
                'hire_date' => $validated['hire_date'],
            ]);

            DB::commit();

            $staff->load('user');
            return response()->json($this->transformStaff($staff), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            // Clean up uploaded file if user creation fails
            if (isset($imagePath) && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
            throw $e;
        }
    }

    public function show(int $id): JsonResponse
    {
        $staff = Staff::with('user')->find($id);
        
        if (!$staff) {
            return response()->json(['message' => 'Staff member not found'], 404);
        }
        
        return response()->json($this->transformStaff($staff));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $staff = Staff::with('user')->find($id);
        
        if (!$staff) {
            return response()->json(['message' => 'Staff member not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255|unique:users,email,' . $staff->user_id,
            'phone' => 'nullable|string|max:20',
            'role' => 'sometimes|required|string',
            'shift' => 'sometimes|required|string',
            'hourly_rate' => 'sometimes|required|numeric|min:0',
            'hire_date' => 'sometimes|required|date',
            'status' => 'sometimes|string|in:active,inactive',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
        ]);

        try {
            DB::beginTransaction();

            // Handle image upload if provided
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($staff->image && Storage::disk('public')->exists($staff->image)) {
                    Storage::disk('public')->delete($staff->image);
                }
                // Store new image
                $imagePath = $request->file('image')->store('staff/images', 'public');
                $validated['image'] = $imagePath;
            }

            // Update user if name or email changed
            if (isset($validated['name']) || isset($validated['email'])) {
                $userData = [];
                if (isset($validated['name'])) {
                    $userData['name'] = $validated['name'];
                }
                if (isset($validated['email'])) {
                    $userData['email'] = $validated['email'];
                }
                $staff->user->update($userData);
            }

            // Update staff profile
            $staffData = array_filter([
                'image' => $validated['image'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'role' => $validated['role'] ?? null,
                'shift' => $validated['shift'] ?? null,
                'hourly_rate' => $validated['hourly_rate'] ?? null,
                'hire_date' => $validated['hire_date'] ?? null,
                'status' => $validated['status'] ?? null,
            ], fn($value) => $value !== null);

            if (!empty($staffData)) {
                $staff->update($staffData);
            }

            DB::commit();

            $staff->refresh()->load('user');
            return response()->json($this->transformStaff($staff));
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $staff = Staff::with('user')->find($id);
        
        if (!$staff) {
            return response()->json(['message' => 'Staff member not found'], 404);
        }

        try {
            DB::beginTransaction();

            // Delete image if exists
            if ($staff->image && Storage::disk('public')->exists($staff->image)) {
                Storage::disk('public')->delete($staff->image);
            }

            $user = $staff->user;
            
            // Delete user - this will cascade delete the staff record due to the foreign key constraint
            // The foreign key on staff.user_id has onDelete('cascade'), so deleting the user
            // will automatically delete the associated staff record
            $user->delete();

            DB::commit();

            return response()->json(['message' => 'Staff member deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
