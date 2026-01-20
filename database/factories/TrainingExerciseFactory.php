<?php

namespace Database\Factories;

use App\Models\Exercise;
use App\Models\TrainingExercise;
use App\Models\TrainingSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrainingExerciseFactory extends Factory
{
    protected $model = TrainingExercise::class;

    public function definition(): array
    {
        return [
            'training_session_id' => TrainingSession::factory(),
            'exercise_id' => Exercise::factory(),
            'exercise_name' => null,
            'sets' => $this->faker->numberBetween(2, 5),
            'reps' => $this->faker->numberBetween(6, 15),
            'weight_kg' => $this->faker->randomFloat(2, 5, 100),
            'rest_seconds' => $this->faker->randomElement([30, 45, 60, 90, 120]),
            'tempo' => null,
            'rir' => null,
            'exercise_type' => 'standard',
            'superset_with' => null,
            'notes' => null,
            'order' => $this->faker->numberBetween(1, 10),
        ];
    }

    public function withNotes(): static
    {
        return $this->state(['notes' => $this->faker->sentence()]);
    }

    public function bodyweight(): static
    {
        return $this->state(['weight_kg' => null]);
    }

    public function standard(): static
    {
        return $this->state(['exercise_type' => 'standard']);
    }

    public function superset(): static
    {
        return $this->state(['exercise_type' => 'superset']);
    }

    public function dropSet(): static
    {
        return $this->state(['exercise_type' => 'drop']);
    }

    public function pyramid(): static
    {
        return $this->state(['exercise_type' => 'pyramid']);
    }

    public function emom(): static
    {
        return $this->state(['exercise_type' => 'emom']);
    }

    public function amrap(): static
    {
        return $this->state(['exercise_type' => 'amrap']);
    }

    public function toFailure(): static
    {
        return $this->state(['exercise_type' => 'failure']);
    }

    public function timed(): static
    {
        return $this->state(['exercise_type' => 'timed']);
    }

    public function withTempo(): static
    {
        return $this->state(['tempo' => '3-1-1-0']);
    }

    public function withRir(int $rir = 2): static
    {
        return $this->state(['rir' => $rir]);
    }

    public function customExercise(string $name): static
    {
        return $this->state([
            'exercise_id' => null,
            'exercise_name' => $name,
        ]);
    }
}
