<?php

namespace Database\Factories;

use App\Models\ClassType;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassTypeFactory extends Factory
{
    protected $model = ClassType::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement(['Yoga', 'Pilates', 'HIIT', 'CrossFit', 'Boxing', 'Spinning', 'Group', 'Personal']);

        return [
            'name' => $name,
            'value' => strtolower(str_replace(' ', '_', $name)),
            'description' => $this->faker->sentence(),
            'color' => $this->faker->hexColor(),
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 10),
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
