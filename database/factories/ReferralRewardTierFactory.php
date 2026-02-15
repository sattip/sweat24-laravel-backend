<?php

namespace Database\Factories;

use App\Models\ReferralRewardTier;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReferralRewardTierFactory extends Factory
{
    protected $model = ReferralRewardTier::class;

    public function definition(): array
    {
        return [
            'referrals_required' => $this->faker->unique()->numberBetween(1, 100),
            'name' => $this->faker->words(2, true) . ' Tier',
            'description' => $this->faker->paragraph(),
            'reward_type' => $this->faker->randomElement(['discount', 'free_month', 'personal_training', 'custom']),
            'discount_percentage' => null,
            'discount_amount' => null,
            'validity_days' => 90,
            'quarterly_only' => true,
            'next_renewal_only' => true,
            'is_active' => true,
            'terms_conditions' => null,
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

    public function bronze(): static
    {
        return $this->state([
            'name' => '1η Σύσταση',
            'referrals_required' => 1,
            'reward_type' => 'discount',
            'discount_percentage' => 10.00,
        ]);
    }

    public function silver(): static
    {
        return $this->state([
            'name' => '3η Σύσταση',
            'referrals_required' => 3,
            'reward_type' => 'discount',
            'discount_percentage' => 25.00,
        ]);
    }

    public function gold(): static
    {
        return $this->state([
            'name' => '5η Σύσταση',
            'referrals_required' => 5,
            'reward_type' => 'free_month',
        ]);
    }
}
