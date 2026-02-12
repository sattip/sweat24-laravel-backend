<?php

namespace Tests\Unit\Models;

use App\Models\PriorityBookingSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriorityBookingSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Clear seeded data to avoid conflicts
        PriorityBookingSettings::query()->delete();
    }

    public function test_can_create_priority_booking_settings(): void
    {
        $settings = PriorityBookingSettings::factory()->create();
        $this->assertDatabaseHas('priority_booking_settings', ['id' => $settings->id]);
    }

    public function test_enabled_state(): void
    {
        $settings = PriorityBookingSettings::factory()->enabled()->create();

        $this->assertTrue($settings->priority_system_enabled);
        $this->assertTrue($settings->auto_release_enabled);
    }

    public function test_disabled_state(): void
    {
        $settings = PriorityBookingSettings::factory()->disabled()->create();

        $this->assertFalse($settings->priority_system_enabled);
        $this->assertFalse($settings->auto_release_enabled);
    }

    public function test_with_priority_packages_state(): void
    {
        $settings = PriorityBookingSettings::factory()->withPriorityPackages([1, 2, 3])->create();

        $this->assertIsArray($settings->priority_packages);
        $this->assertEquals([1, 2, 3], $settings->priority_packages);
    }

    public function test_priority_packages_cast_to_array(): void
    {
        $settings = PriorityBookingSettings::factory()->create();
        $this->assertIsArray($settings->priority_packages);
    }

    public function test_integer_casts(): void
    {
        $settings = PriorityBookingSettings::factory()->create();

        $this->assertIsInt($settings->default_priority_seats);
        $this->assertIsInt($settings->priority_advance_hours);
    }
}
