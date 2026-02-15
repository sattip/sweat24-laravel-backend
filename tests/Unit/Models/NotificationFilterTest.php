<?php

namespace Tests\Unit\Models;

use App\Models\NotificationFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_notification_filter(): void
    {
        $filter = NotificationFilter::factory()->create();
        $this->assertDatabaseHas('notification_filters', ['id' => $filter->id]);
    }

    public function test_active_state(): void
    {
        $filter = NotificationFilter::factory()->active()->create();
        $this->assertTrue($filter->is_active);
    }

    public function test_inactive_state(): void
    {
        $filter = NotificationFilter::factory()->inactive()->create();
        $this->assertFalse($filter->is_active);
    }

    public function test_active_scope(): void
    {
        NotificationFilter::factory()->active()->count(2)->create();
        NotificationFilter::factory()->inactive()->create();

        $this->assertCount(2, NotificationFilter::active()->get());
    }

    public function test_with_package_filter(): void
    {
        $filter = NotificationFilter::factory()->withPackageFilter(['monthly', 'yearly'])->create();

        $this->assertIsArray($filter->criteria);
        $this->assertEquals(['monthly', 'yearly'], $filter->criteria['package_types']);
    }

    public function test_with_membership_filter(): void
    {
        $filter = NotificationFilter::factory()->withMembershipFilter('active')->create();

        $this->assertIsArray($filter->criteria);
        $this->assertEquals('active', $filter->criteria['membership_status']);
    }

    public function test_criteria_cast_to_array(): void
    {
        $filter = NotificationFilter::factory()->create();
        $this->assertIsArray($filter->criteria);
    }

    public function test_is_active_cast_to_boolean(): void
    {
        $filter = NotificationFilter::factory()->create();
        $this->assertIsBool($filter->is_active);
    }
}
