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
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'class_name' => fake()->randomElement(['Yoga', 'Pilates', 'HIIT', 'Zumba']),
            'instructor' => fake()->name(),
            'date' => fake()->dateTimeBetween('now', '+30 days'),
            'time' => fake()->time('H:i'),
            'status' => fake()->randomElement(['confirmed', 'cancelled', 'completed', 'pending']),
            'type' => fake()->randomElement(['group', 'personal']),
            'booking_time' => fake()->dateTimeBetween('-7 days', 'now'),
            'location' => fake()->randomElement(['Studio A', 'Studio B', 'Main Hall']),
        ];
    }
}
