<?php

namespace Database\Factories;

use App\Models\CashRegisterEntry;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CashRegisterEntryFactory extends Factory
{
    protected $model = CashRegisterEntry::class;

    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(['income', 'withdrawal']),
            'amount' => fake()->randomFloat(2, 10, 500),
            'description' => fake()->sentence(),
            'category' => fake()->randomElement(['Package Payment', 'package_usage', 'expense']),
            'user_id' => User::factory(),
            'store_id' => Store::factory(),
            'payment_method' => fake()->randomElement(['cash', 'card', 'package_credit']),
        ];
    }

    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'income',
        ]);
    }

    public function withdrawal(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'withdrawal',
        ]);
    }

    public function packagePayment(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'income',
            'category' => 'Package Payment',
        ]);
    }

    public function packageUsage(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'income',
            'category' => 'package_usage',
            'payment_method' => 'package_credit',
        ]);
    }
}
