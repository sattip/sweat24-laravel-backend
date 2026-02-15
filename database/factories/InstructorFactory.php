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
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'specialties' => [fake()->randomElement(['Yoga', 'Pilates', 'CrossFit', 'Boxing', 'Zumba', 'HIIT'])],
            'bio' => fake()->paragraph(),
            'hourly_rate' => fake()->randomFloat(2, 15, 50),
            'contract_type' => fake()->randomElement(['hourly', 'salary', 'commission']),
            'join_date' => fake()->date(),
            'status' => 'active',
        ];
    }
}
