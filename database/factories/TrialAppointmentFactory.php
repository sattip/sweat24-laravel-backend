<?php

namespace Database\Factories;

use App\Models\Instructor;
use App\Models\Service;
use App\Models\TrialAppointment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrialAppointmentFactory extends Factory
{
    protected $model = TrialAppointment::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'service_id' => Service::factory(),
            'instructor_id' => null,
            'appointment_date' => $this->faker->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'appointment_time' => $this->faker->time('H:i:s'),
            'price' => $this->faker->randomFloat(2, 10, 50),
            'status' => 'pending',
            'notes' => null,
            'confirmed_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
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
            'confirmed_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => 'completed',
            'confirmed_at' => now()->subDays(2),
            'completed_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    public function noShow(): static
    {
        return $this->state(['status' => 'no_show']);
    }

    public function withInstructor(): static
    {
        return $this->state([
            'instructor_id' => Instructor::factory(),
        ]);
    }

    public function past(): static
    {
        return $this->state([
            'appointment_date' => $this->faker->dateTimeBetween('-30 days', '-1 day')->format('Y-m-d'),
        ]);
    }

    public function upcoming(): static
    {
        return $this->state([
            'appointment_date' => $this->faker->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d'),
        ]);
    }
}
