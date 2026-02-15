<?php

namespace Database\Factories;

use App\Models\ExerciseMuscleGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExerciseMuscleGroupFactory extends Factory
{
    protected $model = ExerciseMuscleGroup::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word() . ' Muscle',
            'name_en' => $this->faker->unique()->word() . ' Muscle EN',
            'is_active' => true,
        ];
    }

    public function active(): static
    {
        return $this->state(['is_active' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
