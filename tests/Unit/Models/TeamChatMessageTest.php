<?php

namespace Tests\Unit\Models;

use App\Models\TeamChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamChatMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_team_chat_message(): void
    {
        $message = TeamChatMessage::factory()->create();
        $this->assertDatabaseHas('team_chat_messages', ['id' => $message->id]);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $message = TeamChatMessage::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $message->user);
        $this->assertEquals($user->id, $message->user->id);
    }

    public function test_admin_state(): void
    {
        $message = TeamChatMessage::factory()->admin()->create();
        $this->assertEquals('admin', $message->user_role);
    }

    public function test_trainer_state(): void
    {
        $message = TeamChatMessage::factory()->trainer()->create();
        $this->assertEquals('trainer', $message->user_role);
    }

    public function test_has_message_content(): void
    {
        $message = TeamChatMessage::factory()->create();
        $this->assertNotEmpty($message->message);
    }

    public function test_has_user_name(): void
    {
        $message = TeamChatMessage::factory()->create();
        $this->assertNotEmpty($message->user_name);
    }

    public function test_user_role_is_valid(): void
    {
        $message = TeamChatMessage::factory()->create();
        $this->assertContains($message->user_role, ['admin', 'trainer']);
    }
}
