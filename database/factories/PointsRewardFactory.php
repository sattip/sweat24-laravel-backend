<?php

namespace Database\Factories;

use App\Models\PointsReward;
use Illuminate\Database\Eloquent\Factories\Factory;

class PointsRewardFactory extends Factory
{
    protected $model = PointsReward::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'points_cost' => $this->faker->numberBetween(100, 1000),
            'reward_type' => $this->faker->randomElement(['gift_card', 'free_session', 'product', 'discount', 'premium', 'merchandise']),
            'reward_value' => $this->faker->randomElement(['5€', '10€', '1 μάθημα', '20%', '15%']),
            'image_url' => null,
            'is_active' => true,
            'max_redemptions' => null,
            'current_redemptions' => 0,
            'sort_order' => $this->faker->numberBetween(0, 10),
            'terms_conditions' => null,
            'expires_at' => null,
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

    public function withStock(int $quantity): static
    {
        return $this->state(['max_redemptions' => $quantity]);
    }

    public function discount(): static
    {
        return $this->state([
            'reward_type' => 'discount',
            'reward_value' => '20%',
        ]);
    }

    public function freeSession(): static
    {
        return $this->state([
            'reward_type' => 'free_session',
            'reward_value' => '1 μάθημα',
        ]);
    }
}
