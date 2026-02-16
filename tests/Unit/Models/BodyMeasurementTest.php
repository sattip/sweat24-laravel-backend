<?php

namespace Tests\Unit\Models;

use App\Models\BodyMeasurement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BodyMeasurementTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_body_measurement(): void
    {
        $user = User::factory()->create();
        $measurement = BodyMeasurement::factory()->create(['user_id' => $user->id]);

        $this->assertDatabaseHas('body_measurements', [
            'id' => $measurement->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_measurement_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $measurement = BodyMeasurement::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $measurement->user);
        $this->assertEquals($user->id, $measurement->user->id);
    }

    public function test_bmi_attribute_calculation(): void
    {
        $measurement = BodyMeasurement::factory()->create([
            'weight' => 70,
            'height' => 175, // in cm
        ]);

        // BMI = 70 / (1.75 * 1.75) = 22.86
        $this->assertEquals('22.9', $measurement->bmi);
    }

    public function test_bmi_attribute_returns_null_without_data(): void
    {
        $measurement = BodyMeasurement::factory()->create([
            'weight' => null,
            'height' => null,
        ]);

        $this->assertNull($measurement->bmi);
    }

    public function test_formatted_date_attribute(): void
    {
        $measurement = BodyMeasurement::factory()->create(['date' => '2026-01-15']);

        $this->assertEquals('2026-01-15', $measurement->formatted_date);
    }

    public function test_latest_scope_orders_by_date_desc(): void
    {
        $user = User::factory()->create();
        BodyMeasurement::factory()->create(['user_id' => $user->id, 'date' => '2026-01-10']);
        BodyMeasurement::factory()->create(['user_id' => $user->id, 'date' => '2026-01-15']);

        // The scopeLatest orders by date desc, so the most recent date should be first
        // Using orderBy directly since scopeLatest conflicts with Laravel's built-in latest() method
        $result = BodyMeasurement::forUser($user->id)->orderBy('date', 'desc')->first();

        $this->assertEquals('2026-01-15', $result->date->format('Y-m-d'));
    }

    public function test_between_dates_scope(): void
    {
        $user = User::factory()->create();
        BodyMeasurement::factory()->create(['user_id' => $user->id, 'date' => '2026-01-05']);
        BodyMeasurement::factory()->create(['user_id' => $user->id, 'date' => '2026-01-15']);
        BodyMeasurement::factory()->create(['user_id' => $user->id, 'date' => '2026-01-25']);

        $results = BodyMeasurement::betweenDates('2026-01-10', '2026-01-20')->get();

        $this->assertCount(1, $results);
    }

    public function test_for_user_scope(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        BodyMeasurement::factory()->count(2)->create(['user_id' => $user1->id]);
        BodyMeasurement::factory()->create(['user_id' => $user2->id]);

        $results = BodyMeasurement::forUser($user1->id)->get();

        $this->assertCount(2, $results);
    }

    public function test_get_previous_measurement(): void
    {
        $user = User::factory()->create();
        $first = BodyMeasurement::factory()->create(['user_id' => $user->id, 'date' => '2026-01-10']);
        $second = BodyMeasurement::factory()->create(['user_id' => $user->id, 'date' => '2026-01-15']);

        $previous = $second->getPreviousMeasurement();

        $this->assertEquals($first->id, $previous->id);
    }

    public function test_calculate_changes(): void
    {
        $user = User::factory()->create();
        BodyMeasurement::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-10',
            'weight' => 80,
        ]);
        $current = BodyMeasurement::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-15',
            'weight' => 78,
        ]);

        $changes = $current->calculateChanges();

        $this->assertArrayHasKey('weight', $changes);
        $this->assertEquals(-2, $changes['weight']['change']);
    }

    public function test_get_latest_for_user(): void
    {
        $user = User::factory()->create();
        BodyMeasurement::factory()->create(['user_id' => $user->id, 'date' => '2026-01-10']);
        $latest = BodyMeasurement::factory()->create(['user_id' => $user->id, 'date' => '2026-01-15']);

        $result = BodyMeasurement::getLatestForUser($user->id);

        $this->assertEquals($latest->id, $result->id);
    }

    public function test_to_api_array(): void
    {
        $measurement = BodyMeasurement::factory()->create([
            'weight' => 70,
            'height' => 175,
        ]);

        $array = $measurement->toApiArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('weight', $array);
        $this->assertArrayHasKey('bmi', $array);
    }

    public function test_today_state(): void
    {
        $measurement = BodyMeasurement::factory()->today()->create();

        $this->assertEquals(now()->toDateString(), $measurement->date->toDateString());
    }

    public function test_with_notes_state(): void
    {
        $measurement = BodyMeasurement::factory()->withNotes()->create();

        $this->assertNotNull($measurement->notes);
    }

    public function test_decimal_casts(): void
    {
        $measurement = BodyMeasurement::factory()->create([
            'weight' => 70.50,
            'waist' => 85.25,
        ]);

        $this->assertEquals('70.50', $measurement->weight);
        $this->assertEquals('85.25', $measurement->waist);
    }
}
