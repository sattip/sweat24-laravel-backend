<?php

namespace Database\Factories;

use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactMessageFactory extends Factory
{
    protected $model = ContactMessage::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'name' => $this->faker->name(),
            'email' => $this->faker->email(),
            'phone' => $this->faker->phoneNumber(),
            'subject' => $this->faker->sentence(),
            'message' => $this->faker->paragraph(),
            'status' => 'unread',
            'admin_notes' => null,
            'reply' => null,
            'replied_at' => null,
            'replied_by' => null,
        ];
    }

    public function unread(): static
    {
        return $this->state(['status' => 'unread']);
    }

    public function read(): static
    {
        return $this->state(['status' => 'read']);
    }

    public function replied(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'replied',
            'reply' => $this->faker->paragraph(),
            'replied_at' => now(),
            'replied_by' => \App\Models\User::factory()->create(['role' => 'admin'])->id,
        ]);
    }

    public function archived(): static
    {
        return $this->state(['status' => 'archived']);
    }

    public function fromUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => \App\Models\User::factory(),
        ]);
    }
}
