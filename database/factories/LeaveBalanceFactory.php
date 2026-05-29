<?php

namespace Database\Factories;

use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveBalance>
 */
class LeaveBalanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'leave_type_id' => LeaveType::factory(),
            'year' => now()->year,
            'entitled_days' => fake()->randomFloat(1, 5, 15),
            'carried_over_days' => fake()->randomFloat(1, 0, 5),
        ];
    }
}
