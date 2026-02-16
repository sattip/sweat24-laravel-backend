<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserPushToken;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserPushTokenFactory extends Factory
{
    protected $model = UserPushToken::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token' => $this->faker->sha256(),
            'platform' => $this->faker->randomElement(['ios', 'android', 'web']),
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

    public function ios(): static
    {
        return $this->state(['platform' => 'ios']);
    }

    public function android(): static
    {
        return $this->state(['platform' => 'android']);
    }

    public function web(): static
    {
        return $this->state(['platform' => 'web']);
    }
}
