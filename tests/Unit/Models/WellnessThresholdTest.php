<?php

namespace Tests\Unit\Models;

use App\Models\WellnessThreshold;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WellnessThresholdTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Clear cache and delete seeded data before each test
        Cache::flush();
        WellnessThreshold::query()->delete();
    }

    public function test_can_create_wellness_threshold(): void
    {
        $threshold = WellnessThreshold::factory()->create();

        $this->assertDatabaseHas('wellness_thresholds', [
            'id' => $threshold->id,
            'metric' => $threshold->metric,
        ]);
    }

    public function test_metric_constants(): void
    {
        $this->assertEquals('sleep', WellnessThreshold::METRIC_SLEEP);
        $this->assertEquals('hydration', WellnessThreshold::METRIC_HYDRATION);
        $this->assertEquals('calories', WellnessThreshold::METRIC_CALORIES);
    }

    public function test_level_constants(): void
    {
        $this->assertEquals('green', WellnessThreshold::LEVEL_GREEN);
        $this->assertEquals('orange', WellnessThreshold::LEVEL_ORANGE);
        $this->assertEquals('red', WellnessThreshold::LEVEL_RED);
    }

    public function test_is_active_cast_to_boolean(): void
    {
        $threshold = WellnessThreshold::factory()->create(['is_active' => true]);

        $this->assertIsBool($threshold->is_active);
        $this->assertTrue($threshold->is_active);
    }

    public function test_decimal_casts(): void
    {
        $threshold = WellnessThreshold::factory()->create([
            'min_value' => 5.50,
            'max_value' => 10.25,
        ]);

        $this->assertEquals('5.50', $threshold->min_value);
        $this->assertEquals('10.25', $threshold->max_value);
    }

    public function test_sleep_state(): void
    {
        $threshold = WellnessThreshold::factory()->sleep()->create();

        $this->assertEquals('sleep', $threshold->metric);
    }

    public function test_hydration_state(): void
    {
        $threshold = WellnessThreshold::factory()->hydration()->create();

        $this->assertEquals('hydration', $threshold->metric);
    }

    public function test_calories_state(): void
    {
        $threshold = WellnessThreshold::factory()->calories()->create();

        $this->assertEquals('calories', $threshold->metric);
    }

    public function test_green_state(): void
    {
        $threshold = WellnessThreshold::factory()->green()->create();

        $this->assertEquals('green', $threshold->level);
    }

    public function test_orange_state(): void
    {
        $threshold = WellnessThreshold::factory()->orange()->create();

        $this->assertEquals('orange', $threshold->level);
    }

    public function test_red_state(): void
    {
        $threshold = WellnessThreshold::factory()->red()->create();

        $this->assertEquals('red', $threshold->level);
    }

    public function test_active_state(): void
    {
        $threshold = WellnessThreshold::factory()->active()->create();

        $this->assertTrue($threshold->is_active);
    }

    public function test_inactive_state(): void
    {
        $threshold = WellnessThreshold::factory()->inactive()->create();

        $this->assertFalse($threshold->is_active);
    }

    public function test_get_metric_label(): void
    {
        $this->assertEquals('Ύπνος', WellnessThreshold::getMetricLabel('sleep'));
        $this->assertEquals('Ενυδάτωση', WellnessThreshold::getMetricLabel('hydration'));
        $this->assertEquals('Θερμίδες (% TDEE)', WellnessThreshold::getMetricLabel('calories'));
        $this->assertEquals('unknown', WellnessThreshold::getMetricLabel('unknown'));
    }

    public function test_get_metric_unit(): void
    {
        $this->assertEquals('ώρες', WellnessThreshold::getMetricUnit('sleep'));
        $this->assertEquals('λίτρα', WellnessThreshold::getMetricUnit('hydration'));
        $this->assertEquals('%', WellnessThreshold::getMetricUnit('calories'));
        $this->assertEquals('', WellnessThreshold::getMetricUnit('unknown'));
    }

    public function test_get_level_label(): void
    {
        $this->assertEquals('Φυσιολογικό', WellnessThreshold::getLevelLabel('green'));
        $this->assertEquals('Προειδοποίηση', WellnessThreshold::getLevelLabel('orange'));
        $this->assertEquals('Κρίσιμο', WellnessThreshold::getLevelLabel('red'));
        $this->assertEquals('unknown', WellnessThreshold::getLevelLabel('unknown'));
    }

    public function test_get_level_color(): void
    {
        $this->assertEquals('#22c55e', WellnessThreshold::getLevelColor('green'));
        $this->assertEquals('#f97316', WellnessThreshold::getLevelColor('orange'));
        $this->assertEquals('#ef4444', WellnessThreshold::getLevelColor('red'));
        $this->assertEquals('#6b7280', WellnessThreshold::getLevelColor('unknown'));
    }

    public function test_clear_cache(): void
    {
        // Set a cache key
        Cache::put('wellness_thresholds', ['test' => 'data'], 3600);
        
        $this->assertTrue(Cache::has('wellness_thresholds'));
        
        WellnessThreshold::clearCache();
        
        $this->assertFalse(Cache::has('wellness_thresholds'));
    }
}
