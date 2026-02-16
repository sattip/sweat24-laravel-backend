<?php

namespace Database\Factories;

use App\Models\BookingRequest;
use App\Models\Instructor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingRequestFactory extends Factory
{
    protected $model = BookingRequest::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'service_type' => $this->faker->randomElement(['ems', 'personal']),
            'client_name' => $this->faker->name(),
            'client_email' => $this->faker->safeEmail(),
            'client_phone' => $this->faker->phoneNumber(),
            'preferred_time_slots' => [
                ['date' => now()->addDays(3)->format('Y-m-d'), 'start_time' => '09:00', 'end_time' => '10:00'],
                ['date' => now()->addDays(4)->format('Y-m-d'), 'start_time' => '14:00', 'end_time' => '15:00'],
            ],
            'status' => 'pending',
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
            'confirmed_date' => now()->addDays(3)->format('Y-m-d'),
            'confirmed_time' => '09:00',
        ]);
    }

    public function rejected(): static
    {
        return $this->state([
            'status' => 'rejected',
            'rejection_reason' => $this->faker->sentence(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => 'cancelled',
            'rejection_reason' => $this->faker->sentence(),
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => 'completed',
            'confirmed_date' => now()->subDays(1)->format('Y-m-d'),
            'confirmed_time' => '09:00',
        ]);
    }

    public function ems(): static
    {
        return $this->state([
            'service_type' => 'ems',
        ]);
    }

    public function personal(): static
    {
        return $this->state([
            'service_type' => 'personal',
        ]);
    }

    public function withInstructor(): static
    {
        return $this->state([
            'instructor_id' => Instructor::factory(),
        ]);
    }
}
