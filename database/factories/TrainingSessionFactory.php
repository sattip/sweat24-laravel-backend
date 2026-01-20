<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrainingSessionFactory extends Factory
{
    protected $model = TrainingSession::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'trainer_id' => User::factory(),
            'booking_id' => null,
            'session_date' => $this->faker->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'duration_minutes' => $this->faker->randomElement([30, 45, 60, 90]),
            'session_type' => 'personal',
            'intensity' => $this->faker->numberBetween(1, 10),
            'muscle_groups' => $this->faker->randomElements(['chest', 'back', 'legs', 'shoulders', 'arms', 'core'], 3),
            'includes_cardio' => false,
            'includes_cognitive' => false,
            'includes_mobility' => false,
            'includes_balance' => false,
            'includes_functional' => false,
            'is_total_body' => false,
            'total_volume' => null,
            'notes' => null,
        ];
    }

    public function personal(): static
    {
        return $this->state(['session_type' => 'personal']);
    }

    public function semiPersonal(): static
    {
        return $this->state(['session_type' => 'semi_personal']);
    }

    public function group(): static
    {
        return $this->state(['session_type' => 'group']);
    }

    public function lowIntensity(): static
    {
        return $this->state(['intensity' => $this->faker->numberBetween(1, 3)]);
    }

    public function mediumIntensity(): static
    {
        return $this->state(['intensity' => $this->faker->numberBetween(4, 6)]);
    }

    public function highIntensity(): static
    {
        return $this->state(['intensity' => $this->faker->numberBetween(7, 10)]);
    }

    public function totalBody(): static
    {
        return $this->state([
            'is_total_body' => true,
            'muscle_groups' => ['chest', 'back', 'legs', 'shoulders', 'arms', 'core'],
        ]);
    }

    public function withCardio(): static
    {
        return $this->state(['includes_cardio' => true]);
    }

    public function withMobility(): static
    {
        return $this->state(['includes_mobility' => true]);
    }

    public function withBooking(): static
    {
        return $this->state(['booking_id' => Booking::factory()]);
    }

    public function past(): static
    {
        return $this->state([
            'session_date' => $this->faker->dateTimeBetween('-30 days', '-1 day')->format('Y-m-d'),
        ]);
    }

    public function upcoming(): static
    {
        return $this->state([
            'session_date' => $this->faker->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d'),
        ]);
    }
}
