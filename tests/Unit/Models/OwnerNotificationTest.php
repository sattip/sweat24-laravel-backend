<?php

namespace Tests\Unit\Models;

use App\Models\OwnerNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_owner_notification(): void
    {
        $user = User::factory()->create();
        $notification = OwnerNotification::factory()->create(['user_id' => $user->id]);

        $this->assertDatabaseHas('owner_notifications', [
            'id' => $notification->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_owner_notification_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $notification = OwnerNotification::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $notification->user);
        $this->assertEquals($user->id, $notification->user->id);
    }

    public function test_graceful_cancellation_state(): void
    {
        $notification = OwnerNotification::factory()->gracefulCancellation()->create();

        $this->assertEquals('graceful_cancellation', $notification->type);
    }

    public function test_package_extension_state(): void
    {
        $notification = OwnerNotification::factory()->packageExtension()->create();

        $this->assertEquals('package_extension', $notification->type);
    }

    public function test_special_price_state(): void
    {
        $notification = OwnerNotification::factory()->specialPrice()->create();

        $this->assertEquals('special_price', $notification->type);
    }

    public function test_booking_request_state(): void
    {
        $notification = OwnerNotification::factory()->bookingRequest()->create();

        $this->assertEquals('booking_request', $notification->type);
    }

    public function test_contact_message_state(): void
    {
        $notification = OwnerNotification::factory()->contactMessage()->create();

        $this->assertEquals('contact_message', $notification->type);
    }

    public function test_general_state(): void
    {
        $notification = OwnerNotification::factory()->general()->create();

        $this->assertEquals('general', $notification->type);
    }

    public function test_read_state(): void
    {
        $notification = OwnerNotification::factory()->read()->create();

        $this->assertTrue($notification->is_read);
    }

    public function test_unread_state(): void
    {
        $notification = OwnerNotification::factory()->unread()->create();

        $this->assertFalse($notification->is_read);
    }

    public function test_high_priority_state(): void
    {
        $notification = OwnerNotification::factory()->highPriority()->create();

        $this->assertEquals('high', $notification->priority);
    }

    public function test_low_priority_state(): void
    {
        $notification = OwnerNotification::factory()->lowPriority()->create();

        $this->assertEquals('low', $notification->priority);
    }

    public function test_is_read_cast_to_boolean(): void
    {
        $notification = OwnerNotification::factory()->create(['is_read' => true]);

        $this->assertIsBool($notification->is_read);
        $this->assertTrue($notification->is_read);
    }

    public function test_metadata_cast_to_array(): void
    {
        $notification = OwnerNotification::factory()->create([
            'metadata' => ['key' => 'value'],
        ]);

        $this->assertIsArray($notification->metadata);
        $this->assertEquals('value', $notification->metadata['key']);
    }

    public function test_trainer_and_customer_name_stored(): void
    {
        $notification = OwnerNotification::factory()->create([
            'trainer_name' => 'John Trainer',
            'customer_name' => 'Jane Customer',
        ]);

        $this->assertEquals('John Trainer', $notification->trainer_name);
        $this->assertEquals('Jane Customer', $notification->customer_name);
    }

    public function test_booking_and_package_id_stored(): void
    {
        $notification = OwnerNotification::factory()->create([
            'booking_id' => '12345',
            'package_id' => '67890',
        ]);

        $this->assertEquals('12345', $notification->booking_id);
        $this->assertEquals('67890', $notification->package_id);
    }
}
