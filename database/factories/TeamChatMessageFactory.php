<?php

namespace Database\Factories;

use App\Models\TeamChatMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamChatMessageFactory extends Factory
{
    protected $model = TeamChatMessage::class;

    public function definition(): array
    {
        $user = User::factory()->create();

        return [
            'user_id' => $user->id,
            'message' => $this->faker->paragraph(),
            'user_name' => $user->name,
            'user_role' => $this->faker->randomElement(['admin', 'trainer']),
        ];
    }

    public function admin(): static
    {
        return $this->state(['user_role' => 'admin']);
    }

    public function trainer(): static
    {
        return $this->state(['user_role' => 'trainer']);
    }
}
