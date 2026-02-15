<?php

namespace Database\Factories;

use App\Models\Referral;
use App\Models\ReferralCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReferralFactory extends Factory
{
    protected $model = Referral::class;

    public function definition(): array
    {
        return [
            'referrer_id' => User::factory(),
            'referred_user_id' => User::factory(),
            'referral_code_id' => ReferralCode::factory(),
            'status' => 'pending',
            'joined_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state([
            'status' => 'pending',
        ]);
    }

    public function confirmed(): static
    {
        return $this->state([
            'status' => 'confirmed',
        ]);
    }

    public function rewarded(): static
    {
        return $this->state([
            'status' => 'rewarded',
        ]);
    }
}
