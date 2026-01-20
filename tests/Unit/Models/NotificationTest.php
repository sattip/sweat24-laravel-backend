<?php

namespace Tests\Unit\Models;

use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_notification(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create(['created_by' => $user->id]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'created_by' => $user->id,
        ]);
    }

    public function test_notification_belongs_to_creator(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create(['created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $notification->creator);
        $this->assertEquals($user->id, $notification->creator->id);
    }

    public function test_notification_has_many_recipients(): void
    {
        $notification = Notification::factory()->create();
        NotificationRecipient::factory()->count(3)->create(['notification_id' => $notification->id]);

        $this->assertCount(3, $notification->recipients);
        $this->assertInstanceOf(NotificationRecipient::class, $notification->recipients->first());
    }

    public function test_notification_types_constant(): void
    {
        $this->assertEquals('info', Notification::TYPE_INFO);
        $this->assertEquals('warning', Notification::TYPE_WARNING);
        $this->assertEquals('success', Notification::TYPE_SUCCESS);
        $this->assertEquals('error', Notification::TYPE_ERROR);
        $this->assertEquals('offer', Notification::TYPE_OFFER);
        $this->assertEquals('party_event', Notification::TYPE_PARTY_EVENT);
        $this->assertEquals('order_status', Notification::TYPE_ORDER_STATUS);
    }

    public function test_notification_priority_constants(): void
    {
        $this->assertEquals('low', Notification::PRIORITY_LOW);
        $this->assertEquals('medium', Notification::PRIORITY_MEDIUM);
        $this->assertEquals('high', Notification::PRIORITY_HIGH);
    }

    public function test_get_types_returns_array(): void
    {
        $types = Notification::getTypes();

        $this->assertIsArray($types);
        $this->assertArrayHasKey(Notification::TYPE_INFO, $types);
        $this->assertArrayHasKey(Notification::TYPE_OFFER, $types);
    }

    public function test_get_type_icons_returns_array(): void
    {
        $icons = Notification::getTypeIcons();

        $this->assertIsArray($icons);
        $this->assertEquals('info-circle', $icons[Notification::TYPE_INFO]);
        $this->assertEquals('tag', $icons[Notification::TYPE_OFFER]);
    }

    public function test_get_type_colors_returns_array(): void
    {
        $colors = Notification::getTypeColors();

        $this->assertIsArray($colors);
        $this->assertEquals('blue', $colors[Notification::TYPE_INFO]);
        $this->assertEquals('purple', $colors[Notification::TYPE_OFFER]);
    }

    public function test_is_scheduled_returns_true_when_scheduled(): void
    {
        $notification = Notification::factory()->scheduled()->create();

        $this->assertTrue($notification->isScheduled());
    }

    public function test_is_scheduled_returns_false_when_not_scheduled(): void
    {
        $notification = Notification::factory()->draft()->create();

        $this->assertFalse($notification->isScheduled());
    }

    public function test_is_offer_returns_true_for_offer_type(): void
    {
        $notification = Notification::factory()->offer()->create();

        $this->assertTrue($notification->isOffer());
    }

    public function test_is_offer_returns_false_for_other_types(): void
    {
        $notification = Notification::factory()->info()->create();

        $this->assertFalse($notification->isOffer());
    }

    public function test_is_party_event_returns_true_for_party_event_type(): void
    {
        $notification = Notification::factory()->partyEvent()->create();

        $this->assertTrue($notification->isPartyEvent());
    }

    public function test_get_type_label_returns_correct_label(): void
    {
        $notification = Notification::factory()->info()->create();

        $this->assertNotEmpty($notification->getTypeLabel());
    }

    public function test_get_type_icon_returns_correct_icon(): void
    {
        $notification = Notification::factory()->info()->create();

        $this->assertEquals('info-circle', $notification->getTypeIcon());
    }

    public function test_get_type_color_returns_correct_color(): void
    {
        $notification = Notification::factory()->info()->create();

        $this->assertEquals('blue', $notification->getTypeColor());
    }

    public function test_pending_scope(): void
    {
        Notification::factory()->draft()->create();
        Notification::factory()->scheduled()->create();
        Notification::factory()->sent()->create();

        $pending = Notification::pending()->get();

        $this->assertCount(2, $pending);
    }

    public function test_sent_scope(): void
    {
        Notification::factory()->draft()->create();
        Notification::factory()->sent()->create();
        Notification::factory()->sent()->create();

        $sent = Notification::sent()->get();

        $this->assertCount(2, $sent);
    }

    public function test_by_type_scope(): void
    {
        Notification::factory()->info()->create();
        Notification::factory()->offer()->create();
        Notification::factory()->offer()->create();

        $offers = Notification::byType(Notification::TYPE_OFFER)->get();

        $this->assertCount(2, $offers);
    }

    public function test_notification_casts_channels_to_array(): void
    {
        $notification = Notification::factory()->create(['channels' => ['push', 'email']]);

        $this->assertIsArray($notification->channels);
        $this->assertContains('push', $notification->channels);
    }

    public function test_notification_casts_datetime_fields(): void
    {
        $notification = Notification::factory()->sent()->create();

        $this->assertInstanceOf(\Carbon\Carbon::class, $notification->sent_at);
    }

    public function test_delivered_recipients_returns_only_delivered(): void
    {
        $notification = Notification::factory()->create();
        NotificationRecipient::factory()->delivered()->count(2)->create(['notification_id' => $notification->id]);
        NotificationRecipient::factory()->pending()->count(3)->create(['notification_id' => $notification->id]);

        $this->assertCount(2, $notification->deliveredRecipients);
    }

    public function test_read_recipients_returns_only_read(): void
    {
        $notification = Notification::factory()->create();
        NotificationRecipient::factory()->read()->count(2)->create(['notification_id' => $notification->id]);
        NotificationRecipient::factory()->delivered()->count(3)->create(['notification_id' => $notification->id]);

        $this->assertCount(2, $notification->readRecipients);
    }
}
