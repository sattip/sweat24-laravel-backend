<?php

namespace Database\Factories;

use App\Models\ReferralReward;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReferralRewardFactory extends Factory
{
    protected $model = ReferralReward::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'type' => $this->faker->randomElement(['personal_training', 'discount', 'free_month', 'custom']),
            'value' => $this->faker->randomFloat(2, 5, 50),
            'status' => 'available',
            'earned_at' => now(),
            'referrals_required' => 1,
        ];
    }

    public function available(): static
    {
        return $this->state([
            'status' => 'available',
        ]);
    }

    public function redeemed(): static
    {
        return $this->state([
            'status' => 'redeemed',
            'redeemed_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state([
            'status' => 'expired',
            'expires_at' => now()->subDay(),
        ]);
    }

    public function withRequirements(int $referrals = 5): static
    {
        return $this->state([
            'referrals_required' => $referrals,
        ]);
    }

    public function discount(float $value = 20): static
    {
        return $this->state([
            'type' => 'discount',
            'value' => $value,
        ]);
    }

    public function freeMonth(): static
    {
        return $this->state([
            'type' => 'free_month',
            'value' => null,
        ]);
    }

    public function personalTraining(): static
    {
        return $this->state([
            'type' => 'personal_training',
            'value' => null,
        ]);
    }
}
