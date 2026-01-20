<?php

namespace Database\Factories;

use App\Models\ReferralCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ReferralCodeFactory extends Factory
{
    protected $model = ReferralCode::class;

    public function definition(): array
    {
        $code = strtoupper(Str::random(6)) . $this->faker->numberBetween(1000, 9999);

        return [
            'user_id' => User::factory(),
            'code' => $code,
            'link' => "sweat24.com/join?ref={$code}",
            'total_referrals' => 0,
            'is_active' => true,
        ];
    }

    public function active(): static
    {
        return $this->state([
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state([
            'is_active' => false,
        ]);
    }

    public function withReferrals(int $count): static
    {
        return $this->state([
            'total_referrals' => $count,
        ]);
    }
}
