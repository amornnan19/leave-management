<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a known HR user (idempotent by email)
        User::firstOrCreate(
            ['email' => 'hr@example.com'],
            [
                'name' => 'HR Admin',
                'password' => Hash::make('password'),
                'role' => UserRole::Hr,
                'employee_code' => 'EMP-0001',
                'email_verified_at' => now(),
            ]
        );

        $this->call([
            LeaveTypeSeeder::class,
            HolidaySeeder::class,
            DemoSeeder::class,
        ]);
    }
}
