<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'code' => 'ANNUAL',
                'name' => 'Annual Leave',
                'color' => '#6366f1',
                'is_paid' => true,
                'requires_attachment' => false,
                'default_days_per_year' => 10.0,
                'max_consecutive_days' => null,
                'is_active' => true,
            ],
            [
                'code' => 'SICK',
                'name' => 'Sick Leave',
                'color' => '#ef4444',
                'is_paid' => true,
                'requires_attachment' => true,
                'default_days_per_year' => 30.0,
                'max_consecutive_days' => null,
                'is_active' => true,
            ],
            [
                'code' => 'PERSONAL',
                'name' => 'Personal Leave',
                'color' => '#f59e0b',
                'is_paid' => true,
                'requires_attachment' => false,
                'default_days_per_year' => 3.0,
                'max_consecutive_days' => null,
                'is_active' => true,
            ],
            [
                'code' => 'MATERNITY',
                'name' => 'Maternity Leave',
                'color' => '#ec4899',
                'is_paid' => true,
                'requires_attachment' => false,
                'default_days_per_year' => 98.0,
                'max_consecutive_days' => 98,
                'is_active' => true,
            ],
            [
                'code' => 'UNPAID',
                'name' => 'Unpaid Leave',
                'color' => '#6b7280',
                'is_paid' => false,
                'requires_attachment' => false,
                'default_days_per_year' => 0.0,
                'max_consecutive_days' => null,
                'is_active' => true,
            ],
        ];

        foreach ($types as $type) {
            LeaveType::firstOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}
