<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $holidays = [
            ['name' => "New Year's Day", 'date' => '2026-01-01', 'is_recurring' => true],
            ['name' => 'Makha Bucha Day', 'date' => '2026-03-03', 'is_recurring' => false],
            ['name' => 'Chakri Memorial Day', 'date' => '2026-04-06', 'is_recurring' => true],
            ['name' => 'Songkran Festival', 'date' => '2026-04-13', 'is_recurring' => true],
            ['name' => 'Songkran Festival', 'date' => '2026-04-14', 'is_recurring' => true],
            ['name' => 'Songkran Festival', 'date' => '2026-04-15', 'is_recurring' => true],
            ['name' => 'Labour Day', 'date' => '2026-05-01', 'is_recurring' => true],
            ['name' => 'Coronation Day', 'date' => '2026-05-04', 'is_recurring' => true],
            ['name' => 'Visakha Bucha Day', 'date' => '2026-05-31', 'is_recurring' => false],
            ['name' => "Queen's Birthday", 'date' => '2026-08-12', 'is_recurring' => true],
            ['name' => 'Chulalongkorn Day', 'date' => '2026-10-23', 'is_recurring' => true],
            ['name' => "King's Birthday", 'date' => '2026-12-05', 'is_recurring' => true],
            ['name' => 'Constitution Day', 'date' => '2026-12-10', 'is_recurring' => true],
            ['name' => "New Year's Eve", 'date' => '2026-12-31', 'is_recurring' => true],
        ];

        foreach ($holidays as $holiday) {
            Holiday::firstOrCreate(
                ['date' => $holiday['date']],
                $holiday
            );
        }
    }
}
