<?php

namespace Database\Factories;

use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

class PackageFactory extends Factory
{
    protected $model = Package::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Basic', 'Premium', 'Gold', 'Platinum']) . ' Package',
            'duration' => fake()->randomElement([7, 14, 30, 60, 90]),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 50, 500),
            'sessions' => fake()->numberBetween(5, 50),
            'status' => 'active',
        ];
    }
}
