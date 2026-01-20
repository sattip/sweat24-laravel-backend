<?php

namespace Tests\Unit\Models;

use App\Models\PointsSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PointsSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_points_setting(): void
    {
        $setting = PointsSetting::factory()->create();
        $this->assertDatabaseHas('points_settings', ['id' => $setting->id]);
    }

    public function test_active_state(): void
    {
        $setting = PointsSetting::factory()->active()->create();
        $this->assertTrue($setting->is_active);
    }

    public function test_inactive_state(): void
    {
        $setting = PointsSetting::factory()->inactive()->create();
        $this->assertFalse($setting->is_active);
    }

    public function test_points_per_euro_state(): void
    {
        $setting = PointsSetting::factory()->pointsPerEuro()->create();

        $this->assertEquals('points_per_euro', $setting->key);
        $this->assertEquals('10', $setting->value);
    }

    public function test_referral_bonus_state(): void
    {
        $setting = PointsSetting::factory()->referralBonus()->create();

        $this->assertEquals('referral_bonus', $setting->key);
        $this->assertEquals('100', $setting->value);
    }

    public function test_key_is_unique(): void
    {
        $setting1 = PointsSetting::factory()->create(['key' => 'unique_key_1']);
        $setting2 = PointsSetting::factory()->create(['key' => 'unique_key_2']);

        $this->assertNotEquals($setting1->key, $setting2->key);
    }

    public function test_value_is_numeric(): void
    {
        $setting = PointsSetting::factory()->create();
        $this->assertIsNumeric($setting->value);
    }
}
