<?php

namespace Tests\Unit\Models;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatMessageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Disable events to prevent ChatMessage::booted from running during tests
        ChatMessage::unsetEventDispatcher();
    }

    protected function tearDown(): void
    {
        ChatMessage::setEventDispatcher(app('events'));
        parent::tearDown();
    }

    public function test_can_create_chat_message(): void
    {
        $conversation = ChatConversation::factory()->create();
        $sender = User::factory()->create();

        $message = ChatMessage::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
        ]);

        $this->assertDatabaseHas('chat_messages', [
            'id' => $message->id,
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
        ]);
    }

    public function test_message_belongs_to_conversation(): void
    {
        $conversation = ChatConversation::factory()->create();
        $message = ChatMessage::factory()->create(['conversation_id' => $conversation->id]);

        $this->assertInstanceOf(ChatConversation::class, $message->conversation);
        $this->assertEquals($conversation->id, $message->conversation->id);
    }

    public function test_message_belongs_to_sender(): void
    {
        $sender = User::factory()->create();
        $message = ChatMessage::factory()->create(['sender_id' => $sender->id]);

        $this->assertInstanceOf(User::class, $message->sender);
        $this->assertEquals($sender->id, $message->sender->id);
    }

    public function test_from_user_state(): void
    {
        $message = ChatMessage::factory()->fromUser()->create();

        $this->assertEquals('user', $message->sender_type);
    }

    public function test_from_admin_state(): void
    {
        $message = ChatMessage::factory()->fromAdmin()->create();

        $this->assertEquals('admin', $message->sender_type);
    }

    public function test_read_state(): void
    {
        $message = ChatMessage::factory()->read()->create();

        $this->assertTrue($message->is_read);
        $this->assertNotNull($message->read_at);
    }

    public function test_unread_state(): void
    {
        $message = ChatMessage::factory()->unread()->create();

        $this->assertFalse($message->is_read);
        $this->assertNull($message->read_at);
    }

    public function test_with_attachment_state(): void
    {
        $message = ChatMessage::factory()->withAttachment()->create();

        $this->assertNotNull($message->attachment_url);
        $this->assertEquals('document', $message->attachment_type);
    }

    public function test_with_image_state(): void
    {
        $message = ChatMessage::factory()->withImage()->create();

        $this->assertNotNull($message->attachment_url);
        $this->assertEquals('image', $message->attachment_type);
    }

    public function test_is_read_cast_to_boolean(): void
    {
        $message = ChatMessage::factory()->create(['is_read' => true]);

        $this->assertIsBool($message->is_read);
        $this->assertTrue($message->is_read);
    }

    public function test_read_at_cast_to_datetime(): void
    {
        $message = ChatMessage::factory()->read()->create();

        $this->assertInstanceOf(\Carbon\Carbon::class, $message->read_at);
    }
}
