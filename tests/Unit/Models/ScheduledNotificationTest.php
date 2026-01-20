<?php

namespace Tests\Unit\Models;

use App\Models\ScheduledNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduledNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_scheduled_notification(): void
    {
        $user = User::factory()->create();
        $notification = ScheduledNotification::factory()->create(['user_id' => $user->id]);

        $this->assertDatabaseHas('scheduled_notifications', [
            'id' => $notification->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_scheduled_notification_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $notification = ScheduledNotification::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $notification->user);
        $this->assertEquals($user->id, $notification->user->id);
    }

    public function test_scheduled_notification_uses_string_id(): void
    {
        $notification = ScheduledNotification::factory()->create();

        $this->assertIsString($notification->id);
    }

    public function test_package_expiry_week_state(): void
    {
        $notification = ScheduledNotification::factory()->packageExpiryWeek()->create();

        $this->assertEquals('package_expiry_week', $notification->type);
    }

    public function test_package_expiry_2days_state(): void
    {
        $notification = ScheduledNotification::factory()->packageExpiry2Days()->create();

        $this->assertEquals('package_expiry_2days', $notification->type);
    }

    public function test_appointment_reminder_state(): void
    {
        $notification = ScheduledNotification::factory()->appointmentReminder()->create();

        $this->assertEquals('appointment_reminder', $notification->type);
    }

    public function test_get_pending_notifications(): void
    {
        // Create pending notifications in the past
        ScheduledNotification::factory()->pending()->create([
            'scheduled_for' => now()->subHour(),
        ]);
        ScheduledNotification::factory()->pending()->create([
            'scheduled_for' => now()->subMinutes(30),
        ]);
        // Create future notification
        ScheduledNotification::factory()->pending()->create([
            'scheduled_for' => now()->addHour(),
        ]);
        // Create sent notification
        ScheduledNotification::factory()->sent()->create([
            'scheduled_for' => now()->subHour(),
        ]);

        $pending = ScheduledNotification::getPendingNotifications();

        $this->assertCount(2, $pending);
    }

    public function test_get_user_pending_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // User's future pending notifications
        ScheduledNotification::factory()->pending()->count(2)->create([
            'user_id' => $user->id,
            'scheduled_for' => now()->addHour(),
        ]);
        // User's sent notification
        ScheduledNotification::factory()->sent()->create([
            'user_id' => $user->id,
            'scheduled_for' => now()->subHour(),
        ]);
        // Other user's notification
        ScheduledNotification::factory()->pending()->create([
            'user_id' => $otherUser->id,
            'scheduled_for' => now()->addHour(),
        ]);

        $pending = ScheduledNotification::getUserPendingNotifications($user->id);

        $this->assertCount(2, $pending);
    }

    public function test_mark_as_sent(): void
    {
        $notification = ScheduledNotification::factory()->pending()->create();

        $result = $notification->markAsSent();
        $notification->refresh();

        $this->assertTrue($result);
        $this->assertTrue($notification->is_sent);
        $this->assertNotNull($notification->sent_at);
    }

    public function test_cancel_for_related(): void
    {
        // Create notifications for related ID
        ScheduledNotification::factory()->pending()->count(2)->create([
            'type' => 'package_expiry_week',
            'related_id' => 123,
        ]);
        // Create notification for different related ID
        ScheduledNotification::factory()->pending()->create([
            'type' => 'package_expiry_week',
            'related_id' => 456,
        ]);
        // Create sent notification (should not be deleted)
        ScheduledNotification::factory()->sent()->create([
            'type' => 'package_expiry_week',
            'related_id' => 123,
        ]);

        $deleted = ScheduledNotification::cancelForRelated('package_expiry_week', 123);

        $this->assertEquals(2, $deleted);
    }

    public function test_data_field_cast_to_array(): void
    {
        $notification = ScheduledNotification::factory()->withData(['key' => 'value'])->create();

        $this->assertIsArray($notification->data);
        $this->assertEquals('value', $notification->data['key']);
    }

    public function test_scheduled_for_cast_to_datetime(): void
    {
        $notification = ScheduledNotification::factory()->create();

        $this->assertInstanceOf(\Carbon\Carbon::class, $notification->scheduled_for);
    }

    public function test_is_sent_cast_to_boolean(): void
    {
        $notification = ScheduledNotification::factory()->sent()->create();

        $this->assertIsBool($notification->is_sent);
        $this->assertTrue($notification->is_sent);
    }
}
