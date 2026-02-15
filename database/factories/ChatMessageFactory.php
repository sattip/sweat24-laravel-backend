<?php

namespace Database\Factories;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChatMessageFactory extends Factory
{
    protected $model = ChatMessage::class;

    public function definition(): array
    {
        return [
            'conversation_id' => ChatConversation::factory(),
            'sender_id' => User::factory(),
            'content' => $this->faker->paragraph(),
            'sender_type' => 'user',
            'is_read' => false,
            'read_at' => null,
            'attachment_url' => null,
            'attachment_type' => null,
        ];
    }

    public function fromUser(): static
    {
        return $this->state(['sender_type' => 'user']);
    }

    public function fromAdmin(): static
    {
        return $this->state(['sender_type' => 'admin']);
    }

    public function read(): static
    {
        return $this->state([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function unread(): static
    {
        return $this->state([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    public function withAttachment(): static
    {
        return $this->state([
            'attachment_url' => 'attachments/' . $this->faker->uuid() . '.pdf',
            'attachment_type' => 'document',
        ]);
    }

    public function withImage(): static
    {
        return $this->state([
            'attachment_url' => 'attachments/' . $this->faker->uuid() . '.jpg',
            'attachment_type' => 'image',
        ]);
    }
}
