<?php

namespace Database\Factories;

use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

class PackageFactory extends Factory
{
    protected $model = Package::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['sessions', 'unlimited', 'time_based']);
        
        return [
            'name' => fake()->randomElement(['Basic', 'Premium', 'Gold', 'Platinum']) . ' Package',
            'type' => $type,
            'duration' => fake()->randomElement([7, 14, 30, 60, 90]),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 50, 500),
            'sessions' => $type === 'sessions' ? fake()->numberBetween(5, 50) : null,
            'status' => 'active',
        ];
    }
}