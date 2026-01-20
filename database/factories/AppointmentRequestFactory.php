<?php

namespace Database\Factories;

use App\Models\AppointmentRequest;
use App\Models\Instructor;
use App\Models\SpecializedService;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentRequestFactory extends Factory
{
    protected $model = AppointmentRequest::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'specialized_service_id' => SpecializedService::factory(),
            'instructor_id' => null,
            'client_name' => $this->faker->name(),
            'client_email' => $this->faker->email(),
            'client_phone' => $this->faker->phoneNumber(),
            'preferred_time_slots' => [
                ['date' => $this->faker->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d'), 'time' => '10:00'],
                ['date' => $this->faker->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d'), 'time' => '14:00'],
            ],
            'notes' => $this->faker->optional()->sentence(),
            'status' => 'pending',
            'confirmed_date' => null,
            'confirmed_time' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function confirmed(): static
    {
        return $this->state([
            'status' => 'confirmed',
            'confirmed_date' => now(),
            'confirmed_time' => '10:00',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => 'cancelled']);
    }

    public function completed(): static
    {
        return $this->state(['status' => 'completed']);
    }

    public function withInstructor(): static
    {
        return $this->state(['instructor_id' => Instructor::factory()]);
    }
}
