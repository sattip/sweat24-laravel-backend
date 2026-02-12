<?php

namespace Tests\Unit\Models;

use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationRecipientTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_notification_recipient(): void
    {
        $notification = Notification::factory()->create();
        $user = User::factory()->create();

        $recipient = NotificationRecipient::factory()->create([
            'notification_id' => $notification->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('notification_recipients', [
            'id' => $recipient->id,
            'notification_id' => $notification->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_recipient_belongs_to_notification(): void
    {
        $notification = Notification::factory()->create();
        $recipient = NotificationRecipient::factory()->create(['notification_id' => $notification->id]);

        $this->assertInstanceOf(Notification::class, $recipient->notification);
        $this->assertEquals($notification->id, $recipient->notification->id);
    }

    public function test_recipient_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $recipient = NotificationRecipient::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $recipient->user);
        $this->assertEquals($user->id, $recipient->user->id);
    }

    public function test_can_mark_as_delivered(): void
    {
        $recipient = NotificationRecipient::factory()->pending()->create();

        $recipient->markAsDelivered(['push']);
        $recipient->refresh();

        $this->assertEquals('delivered', $recipient->delivery_status);
        $this->assertNotNull($recipient->delivered_at);
    }

    public function test_can_mark_as_read(): void
    {
        $notification = Notification::factory()->create(['read_count' => 0]);
        $recipient = NotificationRecipient::factory()->delivered()->create([
            'notification_id' => $notification->id,
        ]);

        $recipient->markAsRead();
        $recipient->refresh();
        $notification->refresh();

        $this->assertNotNull($recipient->read_at);
        $this->assertEquals(1, $notification->read_count);
    }

    public function test_mark_as_read_does_not_increment_twice(): void
    {
        $notification = Notification::factory()->create(['read_count' => 0]);
        $recipient = NotificationRecipient::factory()->delivered()->create([
            'notification_id' => $notification->id,
        ]);

        $recipient->markAsRead();
        $recipient->markAsRead();
        $notification->refresh();

        $this->assertEquals(1, $notification->read_count);
    }

    public function test_can_mark_as_failed(): void
    {
        $recipient = NotificationRecipient::factory()->pending()->create();

        $recipient->markAsFailed('Device token invalid');
        $recipient->refresh();

        $this->assertEquals('failed', $recipient->delivery_status);
        $this->assertEquals('Device token invalid', $recipient->failure_reason);
    }

    public function test_unread_scope(): void
    {
        NotificationRecipient::factory()->pending()->count(2)->create();
        NotificationRecipient::factory()->read()->count(3)->create();

        $unread = NotificationRecipient::unread()->get();

        $this->assertCount(2, $unread);
    }

    public function test_delivered_scope(): void
    {
        NotificationRecipient::factory()->pending()->count(2)->create();
        NotificationRecipient::factory()->delivered()->count(3)->create();

        $delivered = NotificationRecipient::delivered()->get();

        $this->assertCount(3, $delivered);
    }

    public function test_delivery_channels_cast_to_array(): void
    {
        $recipient = NotificationRecipient::factory()->create([
            'delivery_channels' => ['push', 'email'],
        ]);

        $this->assertIsArray($recipient->delivery_channels);
        $this->assertContains('push', $recipient->delivery_channels);
    }

    public function test_datetime_fields_cast_correctly(): void
    {
        $recipient = NotificationRecipient::factory()->read()->create();

        $this->assertInstanceOf(\Carbon\Carbon::class, $recipient->delivered_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $recipient->read_at);
    }
}
