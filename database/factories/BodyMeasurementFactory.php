<?php

namespace Database\Factories;

use App\Models\BodyMeasurement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BodyMeasurementFactory extends Factory
{
    protected $model = BodyMeasurement::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'date' => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'weight' => $this->faker->randomFloat(2, 50, 120),
            'height' => $this->faker->randomFloat(2, 150, 200),
            'waist' => $this->faker->randomFloat(2, 60, 120),
            'hips' => $this->faker->randomFloat(2, 70, 130),
            'chest' => $this->faker->randomFloat(2, 70, 130),
            'arm' => $this->faker->randomFloat(2, 25, 50),
            'thigh' => $this->faker->randomFloat(2, 40, 80),
            'body_fat' => $this->faker->randomFloat(2, 8, 35),
            'notes' => null,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(['user_id' => $user->id]);
    }

    public function today(): static
    {
        return $this->state(['date' => now()->toDateString()]);
    }

    public function withNotes(): static
    {
        return $this->state(['notes' => $this->faker->sentence()]);
    }
}
