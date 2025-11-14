<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Services\JsonStorageService;

class StaffTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_page_loads_without_errors()
    {
        // Create some test staff data
        $storage = app(JsonStorageService::class);
        $testStaff = collect([
            [
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'role' => 'Manager',
                'status' => 'active',
                'hourly_rate' => 20,
                'hire_date' => '2024-01-01'
            ],
            [
                'id' => 2,
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'role' => 'Barista',
                'status' => 'active',
                'hourly_rate' => 15,
                'hire_date' => '2024-02-01'
            ]
        ]);

        $storage->put('staff', $testStaff);

        // Test that the staff page loads without errors
        $response = $this->get('/admin/staff');

        $response->assertStatus(200);

        // Check that the staff data is passed to the view as an array
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Staff')
            ->has('staff')
            ->where('staff', function ($staff) {
                // Verify staff is an array (not a Laravel Collection)
                return is_array($staff) && count($staff) > 0;
            })
        );
    }

    public function test_staff_page_loads_with_empty_staff_data()
    {
        // Clear staff data
        $storage = app(JsonStorageService::class);
        $storage->put('staff', collect([]));

        // Test that the staff page loads without errors even with empty data
        $response = $this->get('/admin/staff');

        $response->assertStatus(200);

        // Check that empty staff data is handled properly
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Staff')
            ->has('staff')
            ->where('staff', [])
        );
    }
}
