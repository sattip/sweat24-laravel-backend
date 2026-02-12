<?php

namespace Database\Factories;

use App\Models\OfferRedemption;
use App\Models\PartnerOffer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfferRedemptionFactory extends Factory
{
    protected $model = OfferRedemption::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'partner_offer_id' => PartnerOffer::factory(),
            'verification_code' => 'S24-' . strtoupper($this->faker->bothify('??????')),
            'status' => 'pending',
            'used_at' => null,
            'expires_at' => now()->addDay(),
            'notes' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state([
            'status' => 'pending',
            'used_at' => null,
        ]);
    }

    public function used(): static
    {
        return $this->state([
            'status' => 'used',
            'used_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state([
            'status' => 'expired',
            'expires_at' => now()->subDay(),
        ]);
    }

    public function withNotes(): static
    {
        return $this->state([
            'notes' => $this->faker->sentence(),
        ]);
    }
}
