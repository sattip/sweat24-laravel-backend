<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventRSVP;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventRSVPFactory extends Factory
{
    protected $model = EventRSVP::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'user_id' => User::factory(),
            'guest_name' => null,
            'guest_email' => null,
            'response' => 'yes',
            'notes' => null,
        ];
    }

    public function attending(): static
    {
        return $this->state(['response' => 'yes']);
    }

    public function notAttending(): static
    {
        return $this->state(['response' => 'no']);
    }

    public function maybe(): static
    {
        return $this->state(['response' => 'maybe']);
    }

    public function asGuest(): static
    {
        return $this->state([
            'user_id' => null,
            'guest_name' => $this->faker->name(),
            'guest_email' => $this->faker->email(),
        ]);
    }
}
