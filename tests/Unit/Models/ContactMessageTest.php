<?php

namespace Tests\Unit\Models;

use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_contact_message(): void
    {
        $message = ContactMessage::factory()->create();

        $this->assertDatabaseHas('contact_messages', [
            'id' => $message->id,
            'email' => $message->email,
        ]);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $message = ContactMessage::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $message->user);
        $this->assertEquals($user->id, $message->user->id);
    }

    public function test_belongs_to_replied_by_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $message = ContactMessage::factory()->create([
            'replied_by' => $admin->id,
            'status' => 'replied',
        ]);

        $this->assertInstanceOf(User::class, $message->repliedByUser);
        $this->assertEquals($admin->id, $message->repliedByUser->id);
    }

    public function test_status_constants(): void
    {
        $this->assertEquals('unread', ContactMessage::STATUS_UNREAD);
        $this->assertEquals('read', ContactMessage::STATUS_READ);
        $this->assertEquals('replied', ContactMessage::STATUS_REPLIED);
        $this->assertEquals('archived', ContactMessage::STATUS_ARCHIVED);
    }

    public function test_unread_scope(): void
    {
        ContactMessage::factory()->unread()->count(2)->create();
        ContactMessage::factory()->read()->create();
        ContactMessage::factory()->replied()->create();

        $unread = ContactMessage::unread()->get();

        $this->assertCount(2, $unread);
    }

    public function test_not_archived_scope(): void
    {
        ContactMessage::factory()->unread()->count(2)->create();
        ContactMessage::factory()->read()->create();
        ContactMessage::factory()->archived()->create();

        $notArchived = ContactMessage::notArchived()->get();

        $this->assertCount(3, $notArchived);
    }

    public function test_replied_at_cast_to_datetime(): void
    {
        $message = ContactMessage::factory()->replied()->create();

        $this->assertInstanceOf(\Carbon\Carbon::class, $message->replied_at);
    }

    public function test_unread_state(): void
    {
        $message = ContactMessage::factory()->unread()->create();

        $this->assertEquals('unread', $message->status);
    }

    public function test_read_state(): void
    {
        $message = ContactMessage::factory()->read()->create();

        $this->assertEquals('read', $message->status);
    }

    public function test_replied_state(): void
    {
        $message = ContactMessage::factory()->replied()->create();

        $this->assertEquals('replied', $message->status);
        $this->assertNotNull($message->reply);
        $this->assertNotNull($message->replied_at);
        $this->assertNotNull($message->replied_by);
    }

    public function test_archived_state(): void
    {
        $message = ContactMessage::factory()->archived()->create();

        $this->assertEquals('archived', $message->status);
    }

    public function test_from_user_state(): void
    {
        $message = ContactMessage::factory()->fromUser()->create();

        $this->assertNotNull($message->user_id);
        $this->assertInstanceOf(User::class, $message->user);
    }

    public function test_can_create_without_user(): void
    {
        $message = ContactMessage::factory()->create(['user_id' => null]);

        $this->assertNull($message->user_id);
        $this->assertNull($message->user);
    }
}
