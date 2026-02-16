<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\User;
use App\Models\GymClass;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'class_id' => GymClass::factory(),
            'store_id' => Store::factory(),
            'date' => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'time' => fake()->time('H:i'),
            'class_name' => fake()->randomElement(['Morning Yoga', 'Evening Pilates', 'HIIT Training']),
            'instructor' => fake()->name(),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'type' => fake()->randomElement(['group', 'personal']),
            'status' => fake()->randomElement(['confirmed', 'cancelled', 'completed']),
            'location' => fake()->randomElement(['Studio A', 'Studio B', 'Main Hall']),
            'booking_time' => now(),
        ];
    }
}
