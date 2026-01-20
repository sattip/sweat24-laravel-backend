<?php

namespace Database\Factories;

use App\Models\LoyaltyRedemption;
use App\Models\LoyaltyReward;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoyaltyRedemptionFactory extends Factory
{
    protected $model = LoyaltyRedemption::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'loyalty_reward_id' => LoyaltyReward::factory(),
            'points_used' => $this->faker->numberBetween(100, 500),
            'status' => 'pending',
            'redeemed_at' => now(),
            'expires_at' => now()->addDays(30),
            'reward_snapshot' => [
                'name' => $this->faker->words(3, true),
                'points_cost' => $this->faker->numberBetween(100, 500),
            ],
        ];
    }

    public function pending(): static
    {
        return $this->state([
            'status' => 'pending',
        ]);
    }

    public function approved(): static
    {
        return $this->state([
            'status' => 'approved',
        ]);
    }

    public function used(): static
    {
        return $this->state([
            'status' => 'used',
            'used_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => 'cancelled',
        ]);
    }

    public function expired(): static
    {
        return $this->state([
            'status' => 'pending',
            'expires_at' => now()->subDay(),
        ]);
    }

    public function expiringSoon(int $days = 3): static
    {
        return $this->state([
            'status' => 'approved',
            'expires_at' => now()->addDays($days),
        ]);
    }

    public function neverExpires(): static
    {
        return $this->state([
            'expires_at' => null,
        ]);
    }
}
