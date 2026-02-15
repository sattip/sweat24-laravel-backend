<?php

namespace Database\Factories;

use App\Models\Package;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserPackageFactory extends Factory
{
    protected $model = UserPackage::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'package_id' => Package::factory(),
            'name' => fake()->randomElement(['Basic', 'Premium', 'Gold']) . ' Package',
            'assigned_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'expiry_date' => fake()->dateTimeBetween('now', '+3 months'),
            'remaining_sessions' => fake()->numberBetween(1, 20),
            'total_sessions' => fake()->numberBetween(10, 30),
            'status' => 'active',
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'expiry_date' => fake()->dateTimeBetween('-1 month', '-1 day'),
        ]);
    }
}
