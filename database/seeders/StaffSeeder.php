<?php

namespace Database\Seeders;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if staff already exists
        if (Staff::count() > 0) {
            $this->command->info('Staff records already exist. Skipping seeder.');
            return;
        }

        DB::transaction(function () {
            // Create Alice Johnson - Manager
            $alice = User::create([
                'name' => 'Alice Johnson',
                'email' => 'alice@quickserve.com',
                'password' => Hash::make('password'),
            ]);

            Staff::create([
                'user_id' => $alice->id,
                'phone' => '555-0123',
                'role' => 'Manager',
                'shift' => 'Morning',
                'hourly_rate' => 25.00,
                'status' => 'active',
                'hire_date' => '2024-01-15',
            ]);

            // Create Bob Wilson - Barista
            $bob = User::create([
                'name' => 'Bob Wilson',
                'email' => 'bob@quickserve.com',
                'password' => Hash::make('password'),
            ]);

            Staff::create([
                'user_id' => $bob->id,
                'phone' => '555-0124',
                'role' => 'Barista',
                'shift' => 'Morning',
                'hourly_rate' => 18.00,
                'status' => 'active',
                'hire_date' => '2024-02-01',
            ]);
        });

        $this->command->info('Sample staff members created successfully!');
    }
}
