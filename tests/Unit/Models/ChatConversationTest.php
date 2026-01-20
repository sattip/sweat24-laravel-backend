<?php

namespace Tests\Unit\Models;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_chat_conversation(): void
    {
        $user = User::factory()->create();
        $conversation = ChatConversation::factory()->create(['user_id' => $user->id]);

        $this->assertDatabaseHas('chat_conversations', [
            'id' => $conversation->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_conversation_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $conversation = ChatConversation::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $conversation->user);
        $this->assertEquals($user->id, $conversation->user->id);
    }

    public function test_conversation_has_many_messages(): void
    {
        $conversation = ChatConversation::factory()->create();

        // Disable events to prevent ChatMessage::booted from running
        ChatMessage::unsetEventDispatcher();
        ChatMessage::factory()->count(3)->create(['conversation_id' => $conversation->id]);
        ChatMessage::setEventDispatcher(app('events'));

        $this->assertCount(3, $conversation->messages);
        $this->assertInstanceOf(ChatMessage::class, $conversation->messages->first());
    }

    public function test_active_state(): void
    {
        $conversation = ChatConversation::factory()->active()->create();

        $this->assertEquals('active', $conversation->status);
    }

    public function test_closed_state(): void
    {
        $conversation = ChatConversation::factory()->closed()->create();

        $this->assertEquals('closed', $conversation->status);
    }

    public function test_with_unread_messages_state(): void
    {
        $conversation = ChatConversation::factory()->withUnreadMessages()->create();

        $this->assertGreaterThan(0, $conversation->unread_count);
    }

    public function test_with_admin_unread_messages_state(): void
    {
        $conversation = ChatConversation::factory()->withAdminUnreadMessages()->create();

        $this->assertGreaterThan(0, $conversation->admin_unread_count);
    }

    public function test_mark_as_read_by_admin(): void
    {
        $conversation = ChatConversation::factory()->create(['admin_unread_count' => 5]);

        // Disable events and create unread messages from user
        ChatMessage::unsetEventDispatcher();
        ChatMessage::factory()->fromUser()->unread()->count(5)->create(['conversation_id' => $conversation->id]);
        ChatMessage::setEventDispatcher(app('events'));

        $conversation->markAsRead('admin');
        $conversation->refresh();

        $this->assertEquals(0, $conversation->admin_unread_count);
    }

    public function test_mark_as_read_by_user(): void
    {
        $conversation = ChatConversation::factory()->create(['unread_count' => 5]);

        // Disable events and create unread messages from admin
        ChatMessage::unsetEventDispatcher();
        ChatMessage::factory()->fromAdmin()->unread()->count(5)->create(['conversation_id' => $conversation->id]);
        ChatMessage::setEventDispatcher(app('events'));

        $conversation->markAsRead('user');
        $conversation->refresh();

        $this->assertEquals(0, $conversation->unread_count);
    }

    public function test_last_message_at_cast_to_datetime(): void
    {
        $conversation = ChatConversation::factory()->create();

        $this->assertInstanceOf(\Carbon\Carbon::class, $conversation->last_message_at);
    }

    public function test_unread_count_cast_to_integer(): void
    {
        $conversation = ChatConversation::factory()->create(['unread_count' => 5]);

        $this->assertIsInt($conversation->unread_count);
        $this->assertEquals(5, $conversation->unread_count);
    }
}
