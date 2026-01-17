<?php

namespace Database\Factories;

use App\Models\Instructor;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstructorFactory extends Factory
{
    protected $model = Instructor::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'specialties' => [fake()->randomElement(['Yoga', 'Pilates', 'HIIT', 'Zumba', 'CrossFit'])],
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'hourly_rate' => fake()->randomFloat(2, 15, 50),
            'monthly_bonus' => fake()->optional()->randomFloat(2, 100, 500),
            'commission_rate' => fake()->optional()->randomFloat(4, 0.05, 0.2),
            'contract_type' => fake()->randomElement(['hourly', 'salary', 'commission']),
            'status' => 'active',
            'join_date' => fake()->dateTimeBetween('-2 years', 'now'),
            'total_revenue' => 0,
            'completed_sessions' => 0,
        ];
    }
}
