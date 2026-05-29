<?php

namespace Database\Factories;

use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveType>
 */
class LeaveTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'code' => strtoupper(fake()->unique()->lexify('????')),
            'color' => fake()->hexColor(),
            'is_paid' => true,
            'requires_attachment' => false,
            'default_days_per_year' => fake()->randomFloat(1, 3, 15),
            'max_consecutive_days' => null,
            'is_active' => true,
        ];
    }

    /**
     * Annual leave type.
     */
    public function annual(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Annual Leave',
            'code' => 'ANNUAL',
            'color' => '#6366f1',
            'is_paid' => true,
            'requires_attachment' => false,
            'default_days_per_year' => 10.0,
        ]);
    }

    /**
     * Sick leave type.
     */
    public function sick(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Sick Leave',
            'code' => 'SICK',
            'color' => '#ef4444',
            'is_paid' => true,
            'requires_attachment' => true,
            'default_days_per_year' => 30.0,
        ]);
    }

    /**
     * Personal leave type.
     */
    public function personal(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Personal Leave',
            'code' => 'PERSONAL',
            'color' => '#f59e0b',
            'is_paid' => true,
            'requires_attachment' => false,
            'default_days_per_year' => 3.0,
        ]);
    }
}
