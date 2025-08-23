<?php

namespace Database\Factories;

use App\Models\Session;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

class SessionFactory extends Factory
{
    protected $model = Session::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'user_package_id' => null,
            'session_date' => fake()->dateTimeBetween('now', '+30 days'),
            'session_time' => fake()->time('H:i:s'),
            'duration' => fake()->randomElement([30, 45, 60]),
            'status' => fake()->randomElement(['scheduled', 'completed', 'cancelled']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}