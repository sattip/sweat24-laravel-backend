<?php

namespace Database\Factories;

use App\Models\PointsSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class PointsSettingFactory extends Factory
{
    protected $model = PointsSetting::class;

    public function definition(): array
    {
        return [
            'key' => $this->faker->unique()->slug(2),
            'value' => (string) $this->faker->numberBetween(1, 100),
            'type' => 'integer',
            'description' => $this->faker->sentence(),
            'is_active' => true,
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

    public function pointsPerEuro(): static
    {
        return $this->state([
            'key' => 'points_per_euro',
            'value' => '10',
            'type' => 'integer',
            'description' => 'Points earned per euro spent',
        ]);
    }

    public function referralBonus(): static
    {
        return $this->state([
            'key' => 'referral_bonus',
            'value' => '100',
            'type' => 'integer',
            'description' => 'Points awarded for successful referral',
        ]);
    }
}
