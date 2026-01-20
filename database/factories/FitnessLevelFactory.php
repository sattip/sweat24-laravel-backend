<?php

namespace Database\Factories;

use App\Models\FitnessLevel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FitnessLevelFactory extends Factory
{
    protected $model = FitnessLevel::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'level' => $this->faker->randomElement(['beginner', 'intermediate', 'advanced', 'elite']),
            'assessment_date' => $this->faker->date(),
            'assessed_by' => User::factory(),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function beginner(): static
    {
        return $this->state(['level' => 'beginner']);
    }

    public function intermediate(): static
    {
        return $this->state(['level' => 'intermediate']);
    }

    public function advanced(): static
    {
        return $this->state(['level' => 'advanced']);
    }

    public function elite(): static
    {
        return $this->state(['level' => 'elite']);
    }
}
