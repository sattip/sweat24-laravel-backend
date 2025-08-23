<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\User;
use App\Models\GymClass;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'class_id' => GymClass::factory(),
            'booking_date' => fake()->dateTimeBetween('now', '+30 days'),
            'status' => fake()->randomElement(['confirmed', 'cancelled', 'completed']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}