<?php

namespace Database\Factories;

use App\Models\ChatConversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChatConversationFactory extends Factory
{
    protected $model = ChatConversation::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => 'active',
            'last_message_at' => now(),
            'unread_count' => 0,
            'admin_unread_count' => 0,
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => 'active']);
    }

    public function closed(): static
    {
        return $this->state(['status' => 'closed']);
    }

    public function withUnreadMessages(): static
    {
        return $this->state([
            'unread_count' => $this->faker->numberBetween(1, 10),
        ]);
    }

    public function withAdminUnreadMessages(): static
    {
        return $this->state([
            'admin_unread_count' => $this->faker->numberBetween(1, 10),
        ]);
    }
}
