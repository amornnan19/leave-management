<?php

namespace Database\Factories;

use App\Enums\DayPeriod;
use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('now', '+3 months');

        return [
            'user_id' => User::factory(),
            'leave_type_id' => LeaveType::factory(),
            'start_date' => $startDate,
            'end_date' => $startDate,
            'start_period' => 'full',
            'end_period' => 'full',
            'total_days' => 1.0,
            'reason' => fake()->sentence(),
            'status' => LeaveStatus::Pending,
            'approver_id' => null,
            'approved_at' => null,
            'rejection_reason' => null,
            'attachment_path' => null,
        ];
    }

    /**
     * Mark the request as approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeaveStatus::Approved,
            'approver_id' => User::factory()->manager(),
            'approved_at' => now(),
        ]);
    }

    /**
     * Mark the request as rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeaveStatus::Rejected,
            'approver_id' => User::factory()->manager(),
            'rejection_reason' => fake()->sentence(),
        ]);
    }

    /**
     * Mark the request as cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeaveStatus::Cancelled,
        ]);
    }

    /**
     * Produce a half-day fixture: a single morning period on one day worth 0.5 days.
     */
    public function halfDay(): static
    {
        return $this->state(fn (array $attributes) => [
            'end_date' => $attributes['start_date'],
            'start_period' => DayPeriod::Morning,
            'end_period' => DayPeriod::Morning,
            'total_days' => 0.5,
        ]);
    }
}
