<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\WellnessScore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WellnessScoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_wellness_score(): void
    {
        $user = User::factory()->create();
        $score = WellnessScore::factory()->create(['user_id' => $user->id]);

        $this->assertDatabaseHas('wellness_scores', [
            'id' => $score->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_wellness_score_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $score = WellnessScore::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $score->user);
        $this->assertEquals($user->id, $score->user->id);
    }

    public function test_alert_level_constants(): void
    {
        $this->assertEquals('green', WellnessScore::ALERT_GREEN);
        $this->assertEquals('orange', WellnessScore::ALERT_ORANGE);
        $this->assertEquals('red', WellnessScore::ALERT_RED);
    }

    public function test_get_alert_emoji_green(): void
    {
        $score = WellnessScore::factory()->withGreenAlerts()->create();

        $this->assertEquals('✅', $score->getAlertEmoji());
    }

    public function test_get_alert_emoji_orange(): void
    {
        $score = WellnessScore::factory()->withOrangeAlert()->create();

        $this->assertEquals('⚠️', $score->getAlertEmoji());
    }

    public function test_get_alert_emoji_red(): void
    {
        $score = WellnessScore::factory()->withRedAlert()->create();

        $this->assertEquals('❗', $score->getAlertEmoji());
    }

    public function test_has_submitted_today(): void
    {
        $user = User::factory()->create();
        WellnessScore::factory()->today()->create(['user_id' => $user->id]);

        $this->assertTrue(WellnessScore::hasSubmittedToday($user->id));
    }

    public function test_has_not_submitted_today(): void
    {
        $user = User::factory()->create();
        WellnessScore::factory()->create([
            'user_id' => $user->id,
            'date' => now()->subDay()->toDateString(),
        ]);

        $this->assertFalse(WellnessScore::hasSubmittedToday($user->id));
    }

    public function test_get_today_score(): void
    {
        $user = User::factory()->create();
        $score = WellnessScore::factory()->today()->create(['user_id' => $user->id]);

        $result = WellnessScore::getTodayScore($user->id);

        $this->assertEquals($score->id, $result->id);
    }

    public function test_today_scope(): void
    {
        WellnessScore::factory()->today()->count(2)->create();
        WellnessScore::factory()->create(['date' => now()->subDay()->toDateString()]);

        $results = WellnessScore::today()->get();

        $this->assertCount(2, $results);
    }

    public function test_with_alerts_scope(): void
    {
        WellnessScore::factory()->withGreenAlerts()->create();
        WellnessScore::factory()->withOrangeAlert()->create();
        WellnessScore::factory()->withRedAlert()->create();

        $results = WellnessScore::withAlerts()->get();

        $this->assertCount(2, $results);
    }

    public function test_date_range_scope(): void
    {
        WellnessScore::factory()->create(['date' => '2026-01-05']);
        WellnessScore::factory()->create(['date' => '2026-01-15']);
        WellnessScore::factory()->create(['date' => '2026-01-25']);

        $results = WellnessScore::dateRange('2026-01-10', '2026-01-20')->get();

        $this->assertCount(1, $results);
    }

    public function test_today_state(): void
    {
        $score = WellnessScore::factory()->today()->create();

        $this->assertEquals(now()->toDateString(), $score->date->toDateString());
    }

    public function test_with_green_alerts_state(): void
    {
        $score = WellnessScore::factory()->withGreenAlerts()->create();

        $this->assertEquals(WellnessScore::ALERT_GREEN, $score->overall_alert);
        $this->assertEquals(8.00, $score->sleep_hours);
    }

    public function test_high_wellness_state(): void
    {
        $score = WellnessScore::factory()->highWellness()->create();

        $this->assertGreaterThanOrEqual(80, $score->wellness_score);
    }

    public function test_low_wellness_state(): void
    {
        $score = WellnessScore::factory()->lowWellness()->create();

        $this->assertLessThanOrEqual(40, $score->wellness_score);
    }

    public function test_calculate_wellness_score(): void
    {
        $score = WellnessScore::factory()->create([
            'sleep_hours' => 8,
            'hydration_liters' => 2.5,
            'calories_percentage' => 100,
            'energy_level' => 8,
            'mood_level' => 8,
            'stress_level' => 2,
            'soreness_level' => 2,
        ]);

        $calculatedScore = $score->calculateWellnessScore();

        $this->assertIsInt($calculatedScore);
        $this->assertGreaterThan(0, $calculatedScore);
        $this->assertLessThanOrEqual(100, $calculatedScore);
    }

    public function test_integer_casts(): void
    {
        $score = WellnessScore::factory()->create([
            'calories_consumed' => 2000,
            'energy_level' => 7,
            'wellness_score' => 85,
        ]);

        $this->assertIsInt($score->calories_consumed);
        $this->assertIsInt($score->energy_level);
        $this->assertIsInt($score->wellness_score);
    }

    public function test_decimal_casts(): void
    {
        $score = WellnessScore::factory()->create([
            'sleep_hours' => 7.5,
            'hydration_liters' => 2.25,
        ]);

        $this->assertEquals('7.50', $score->sleep_hours);
        $this->assertEquals('2.25', $score->hydration_liters);
    }
}
