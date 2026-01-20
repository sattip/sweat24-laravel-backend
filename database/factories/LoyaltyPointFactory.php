<?php

namespace Database\Factories;

use App\Models\LoyaltyPoint;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoyaltyPointFactory extends Factory
{
    protected $model = LoyaltyPoint::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'amount' => $this->faker->randomFloat(2, 10, 500),
            'type' => 'earned',
            'source' => $this->faker->randomElement(['purchase', 'referral', 'bonus', 'booking']),
            'description' => $this->faker->sentence(),
            'balance_after' => $this->faker->randomFloat(2, 10, 1000),
            'expires_at' => null,
        ];
    }

    public function earned(): static
    {
        return $this->state([
            'type' => 'earned',
            'amount' => $this->faker->randomFloat(2, 10, 500),
        ]);
    }

    public function spent(): static
    {
        return $this->state([
            'type' => 'spent',
            'amount' => $this->faker->randomFloat(2, -500, -10),
            'source' => 'redemption',
        ]);
    }

    public function expiringSoon(int $days = 15): static
    {
        return $this->state([
            'type' => 'earned',
            'expires_at' => now()->addDays($days),
        ]);
    }

    public function expired(): static
    {
        return $this->state([
            'expires_at' => now()->subDay(),
        ]);
    }

    public function neverExpires(): static
    {
        return $this->state([
            'expires_at' => null,
        ]);
    }
}
