<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Engineering',
            'Human Resources',
            'Finance',
            'Marketing',
            'Operations',
            'Sales',
            'Legal',
            'Product',
            'Design',
            'Customer Support',
        ]);

        return [
            'name' => $name,
            'code' => strtoupper(substr(preg_replace('/[^A-Z]/i', '', $name), 0, 4)),
            'head_user_id' => null,
        ];
    }
}
