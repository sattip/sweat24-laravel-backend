<?php

namespace Database\Factories;

use App\Models\ExerciseEquipment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExerciseEquipmentFactory extends Factory
{
    protected $model = ExerciseEquipment::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word() . ' Equipment',
            'name_en' => $this->faker->unique()->word() . ' Equipment EN',
            'icon' => $this->faker->optional()->word(),
            'description' => $this->faker->optional()->sentence(),
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(1, 100),
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
