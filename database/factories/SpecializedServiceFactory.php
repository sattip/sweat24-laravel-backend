<?php

namespace Database\Factories;

use App\Models\SpecializedService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SpecializedServiceFactory extends Factory
{
    protected $model = SpecializedService::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);
        return [
            'name' => $name,
            'slug' => null, // Let the boot method generate slug from name
            'description' => $this->faker->sentence(),
            'icon' => $this->faker->optional()->word(),
            'is_active' => true,
            'display_order' => $this->faker->numberBetween(1, 100),
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
