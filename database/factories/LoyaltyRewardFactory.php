<?php

namespace Database\Factories;

use App\Models\LoyaltyReward;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoyaltyRewardFactory extends Factory
{
    protected $model = LoyaltyReward::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'points_cost' => $this->faker->numberBetween(100, 1000),
            'validity_days' => 30,
            'type' => $this->faker->randomElement(['discount', 'free_session', 'merchandise']),
            'is_active' => true,
            'current_redemptions' => 0,
        ];
    }

    public function active(): static
    {
        return $this->state([
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state([
            'is_active' => false,
        ]);
    }

    public function expired(): static
    {
        return $this->state([
            'is_active' => true,
            'valid_until' => now()->subDay(),
        ]);
    }

    public function notYetValid(): static
    {
        return $this->state([
            'is_active' => true,
            'valid_from' => now()->addWeek(),
        ]);
    }

    public function withLimit(int $max): static
    {
        return $this->state([
            'max_redemptions' => $max,
            'current_redemptions' => 0,
        ]);
    }

    public function fullyRedeemed(): static
    {
        return $this->state([
            'max_redemptions' => 10,
            'current_redemptions' => 10,
        ]);
    }

    public function discountPercentage(float $percent = 10): static
    {
        return $this->state([
            'type' => 'discount',
            'discount_percentage' => $percent,
            'discount_amount' => null,
        ]);
    }

    public function discountAmount(float $amount = 20): static
    {
        return $this->state([
            'type' => 'discount',
            'discount_amount' => $amount,
            'discount_percentage' => null,
        ]);
    }
}
