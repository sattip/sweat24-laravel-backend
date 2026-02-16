<?php

namespace Tests\Unit\Models;

use App\Models\PriorityBookingSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriorityBookingSettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Clear seeded data to avoid conflicts
        PriorityBookingSetting::query()->delete();
    }

    public function test_can_create_priority_booking_setting(): void
    {
        $setting = PriorityBookingSetting::factory()->create();
        $this->assertDatabaseHas('priority_booking_settings', ['id' => $setting->id]);
    }

    public function test_enabled_state(): void
    {
        $setting = PriorityBookingSetting::factory()->enabled()->create();

        $this->assertTrue($setting->priority_system_enabled);
        $this->assertTrue($setting->auto_release_enabled);
    }

    public function test_disabled_state(): void
    {
        $setting = PriorityBookingSetting::factory()->disabled()->create();

        $this->assertFalse($setting->priority_system_enabled);
        $this->assertFalse($setting->auto_release_enabled);
    }

    public function test_boolean_casts(): void
    {
        $setting = PriorityBookingSetting::factory()->create();

        $this->assertIsBool($setting->priority_system_enabled);
        $this->assertIsBool($setting->auto_release_enabled);
    }

    public function test_default_values(): void
    {
        $setting = PriorityBookingSetting::factory()->create();

        $this->assertNotNull($setting->default_priority_seats);
        $this->assertNotNull($setting->priority_advance_hours);
        $this->assertNotNull($setting->priority_seats_release_hours);
        $this->assertNotNull($setting->priority_booking_window_days);
        $this->assertNotNull($setting->regular_booking_window_days);
    }

    public function test_priority_packages_cast_to_array(): void
    {
        $setting = PriorityBookingSetting::factory()->create([
            'priority_packages' => [1, 2, 3],
        ]);

        $this->assertIsArray($setting->priority_packages);
        $this->assertEquals([1, 2, 3], $setting->priority_packages);
    }

    public function test_get_settings_returns_first_record(): void
    {
        $setting = PriorityBookingSetting::factory()->create();
        $result = PriorityBookingSetting::getSettings();

        $this->assertEquals($setting->id, $result->id);
    }
}
