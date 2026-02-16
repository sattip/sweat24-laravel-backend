<?php

namespace Database\Factories;

use App\Models\ExerciseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExerciseCategoryFactory extends Factory
{
    protected $model = ExerciseCategory::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word() . ' Category',
            'name_en' => $this->faker->unique()->word() . ' Category EN',
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
