<?php

namespace Database\Factories;

use App\Models\PartnerBusiness;
use App\Models\PartnerOffer;
use Illuminate\Database\Eloquent\Factories\Factory;

class PartnerOfferFactory extends Factory
{
    protected $model = PartnerOffer::class;

    public function definition(): array
    {
        return [
            'partner_business_id' => PartnerBusiness::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(['percentage', 'fixed_amount', 'free_item', 'custom']),
            'discount_value' => $this->faker->randomFloat(2, 5, 50),
            'discount_unit' => '%',
            'promo_code' => strtoupper($this->faker->bothify('OFFER##??')),
            'valid_from' => now(),
            'valid_until' => now()->addMonths(3),
            'is_active' => true,
            'usage_limit_per_user' => 1,
            'total_usage_limit' => null,
            'current_usage_count' => 0,
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

    public function expired(): static
    {
        return $this->state([
            'valid_from' => now()->subMonths(6),
            'valid_until' => now()->subMonth(),
        ]);
    }

    public function percentage(): static
    {
        return $this->state([
            'type' => 'percentage',
            'discount_value' => $this->faker->numberBetween(5, 30),
            'discount_unit' => '%',
        ]);
    }

    public function fixedAmount(): static
    {
        return $this->state([
            'type' => 'fixed_amount',
            'discount_value' => $this->faker->randomFloat(2, 5, 20),
            'discount_unit' => '€',
        ]);
    }

    public function freeItem(): static
    {
        return $this->state([
            'type' => 'free_item',
            'discount_value' => null,
            'discount_unit' => null,
        ]);
    }

    public function withLimit(int $limit): static
    {
        return $this->state(['total_usage_limit' => $limit]);
    }

    public function withUserLimit(int $limit): static
    {
        return $this->state(['usage_limit_per_user' => $limit]);
    }
}
