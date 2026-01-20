<?php

namespace Database\Factories;

use App\Models\PerformanceTest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PerformanceTestFactory extends Factory
{
    protected $model = PerformanceTest::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'exercise_name' => $this->faker->randomElement(['Bench Press', 'Squat', 'Deadlift', 'Plank', 'Running']),
            'test_date' => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'weight_kg' => $this->faker->randomFloat(2, 20, 200),
            'reps' => $this->faker->numberBetween(1, 20),
            'time_seconds' => null,
            'category' => 'strength',
            'is_pr' => false,
            'improvement_percentage' => null,
            'notes' => null,
            'trainer_id' => null,
        ];
    }

    public function strength(): static
    {
        return $this->state([
            'category' => 'strength',
            'time_seconds' => null,
        ]);
    }

    public function endurance(): static
    {
        return $this->state([
            'category' => 'endurance',
            'weight_kg' => null,
            'reps' => null,
            'time_seconds' => $this->faker->numberBetween(60, 600),
        ]);
    }

    public function core(): static
    {
        return $this->state([
            'category' => 'core',
            'exercise_name' => 'Plank',
            'weight_kg' => null,
            'time_seconds' => $this->faker->numberBetween(30, 180),
        ]);
    }

    public function personalRecord(): static
    {
        return $this->state(['is_pr' => true]);
    }

    public function withTrainer(): static
    {
        return $this->state(['trainer_id' => User::factory()]);
    }

    public function withImprovement(): static
    {
        return $this->state([
            'improvement_percentage' => $this->faker->randomFloat(2, 1, 20),
        ]);
    }
}
