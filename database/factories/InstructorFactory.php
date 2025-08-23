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
            'specialties' => fake()->randomElement(['Yoga, Pilates', 'CrossFit, HIIT', 'Boxing, MMA', 'Zumba, Dance']),
            'hourly_rate' => fake()->numberBetween(30, 100),
            'contract_type' => fake()->randomElement(['hourly', 'salary', 'commission']),
            'status' => 'active',
            'join_date' => fake()->dateTimeBetween('-2 years', 'now'),
            'bio' => fake()->optional()->paragraph(),
            'title' => fake()->optional()->randomElement(['Senior Trainer', 'Head Coach', 'Fitness Expert']),
        ];
    }
}