<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingReschedule;
use App\Models\GymClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingRescheduleFactory extends Factory
{
    protected $model = BookingReschedule::class;

    public function definition(): array
    {
        $originalDatetime = $this->faker->dateTimeBetween('now', '+1 week');

        return [
            'booking_id' => Booking::factory(),
            'user_id' => User::factory(),
            'original_class_id' => GymClass::factory(),
            'new_class_id' => GymClass::factory(),
            'original_datetime' => $originalDatetime,
            'new_datetime' => $this->faker->dateTimeBetween($originalDatetime, '+2 weeks'),
            'reason' => $this->faker->sentence(),
            'status' => 'pending',
            'requested_at' => now(),
            'processed_at' => null,
            'processed_by' => null,
            'admin_notes' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function approved(): static
    {
        return $this->state([
            'status' => 'approved',
            'processed_at' => now(),
            'processed_by' => User::factory(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state([
            'status' => 'rejected',
            'processed_at' => now(),
            'processed_by' => User::factory(),
        ]);
    }
}
