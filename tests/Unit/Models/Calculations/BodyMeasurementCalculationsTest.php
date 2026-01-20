<?php

namespace Tests\Unit\Models\Calculations;

use App\Models\BodyMeasurement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BodyMeasurementCalculationsTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // BMI Calculation Tests
    // ==========================================

    public function test_calculates_bmi_correctly(): void
    {
        $user = User::factory()->create();

        $measurement = BodyMeasurement::create([
            'user_id' => $user->id,
            'date' => today(),
            'weight' => 70,
            'height' => 175,
        ]);

        // BMI = 70 / (1.75 * 1.75) = 70 / 3.0625 = 22.86
        $this->assertEquals('22.9', $measurement->bmi);
    }

    public function test_bmi_returns_null_without_weight(): void
    {
        $user = User::factory()->create();

        $measurement = BodyMeasurement::create([
            'user_id' => $user->id,
            'date' => today(),
            'height' => 175,
        ]);

        $this->assertNull($measurement->bmi);
    }

    public function test_bmi_returns_null_without_height(): void
    {
        $user = User::factory()->create();

        $measurement = BodyMeasurement::create([
            'user_id' => $user->id,
            'date' => today(),
            'weight' => 70,
        ]);

        $this->assertNull($measurement->bmi);
    }

    public function test_bmi_calculation_with_different_values(): void
    {
        $user = User::factory()->create();

        // Test underweight BMI
        $underweight = BodyMeasurement::create([
            'user_id' => $user->id,
            'date' => today()->subDays(3),
            'weight' => 50,
            'height' => 175,
        ]);
        // BMI = 50 / 3.0625 = 16.3
        $this->assertEquals('16.3', $underweight->bmi);

        // Test overweight BMI
        $overweight = BodyMeasurement::create([
            'user_id' => $user->id,
            'date' => today()->subDays(2),
            'weight' => 95,
            'height' => 175,
        ]);
        // BMI = 95 / 3.0625 = 31.0
        $this->assertEquals('31.0', $overweight->bmi);
    }

    // ==========================================
    // Scope Tests
    // ==========================================

    public function test_scope_for_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        BodyMeasurement::create([
            'user_id' => $user1->id,
            'date' => today(),
            'weight' => 75,
        ]);

        BodyMeasurement::create([
            'user_id' => $user1->id,
            'date' => today()->subMonth(),
            'weight' => 77,
        ]);

        BodyMeasurement::create([
            'user_id' => $user2->id,
            'date' => today(),
            'weight' => 80,
        ]);

        $user1Measurements = BodyMeasurement::forUser($user1->id)->get();

        $this->assertCount(2, $user1Measurements);
    }

    public function test_scope_between_dates(): void
    {
        $user = User::factory()->create();

        BodyMeasurement::create([
            'user_id' => $user->id,
            'date' => '2026-01-01',
            'weight' => 80,
        ]);

        BodyMeasurement::create([
            'user_id' => $user->id,
            'date' => '2026-01-15',
            'weight' => 78,
        ]);

        BodyMeasurement::create([
            'user_id' => $user->id,
            'date' => '2026-02-01',
            'weight' => 76,
        ]);

        $januaryMeasurements = BodyMeasurement::betweenDates('2026-01-01', '2026-01-31')->get();

        $this->assertCount(2, $januaryMeasurements);
    }

    public function test_scope_latest_orders_by_date_desc(): void
    {
        $user = User::factory()->create();

        BodyMeasurement::create([
            'user_id' => $user->id,
            'date' => '2026-01-15',
            'weight' => 78,
        ]);

        BodyMeasurement::create([
            'user_id' => $user->id,
            'date' => '2026-01-01',
            'weight' => 80,
        ]);

        BodyMeasurement::create([
            'user_id' => $user->id,
            'date' => '2026-02-01',
            'weight' => 76,
        ]);

        // Use the custom latest scope explicitly
        $measurements = BodyMeasurement::query()->latest('date')->get();

        $this->assertEquals(76, $measurements->first()->weight);
        $this->assertEquals(80, $measurements->last()->weight);
    }

    // ==========================================
    // API Array Format Tests
    // ==========================================

    public function test_to_api_array_formats_correctly(): void
    {
        $user = User::factory()->create();

        $measurement = BodyMeasurement::create([
            'user_id' => $user->id,
            'date' => '2026-01-15',
            'weight' => 75.5,
            'height' => 175,
            'waist' => 85.0,
            'notes' => 'Good progress',
        ]);

        $apiArray = $measurement->toApiArray();

        $this->assertArrayHasKey('id', $apiArray);
        $this->assertArrayHasKey('date', $apiArray);
        $this->assertArrayHasKey('weight', $apiArray);
        $this->assertArrayHasKey('bmi', $apiArray);
        $this->assertEquals('2026-01-15', $apiArray['date']);
        $this->assertEquals('75.50', $apiArray['weight']);
    }

    // ==========================================
    // BMI Edge Cases
    // ==========================================

    public function test_bmi_with_short_height(): void
    {
        $user = User::factory()->create();

        $measurement = BodyMeasurement::create([
            'user_id' => $user->id,
            'date' => today(),
            'weight' => 60,
            'height' => 150, // 1.5m
        ]);

        // BMI = 60 / (1.5 * 1.5) = 60 / 2.25 = 26.67
        $this->assertEquals('26.7', $measurement->bmi);
    }

    public function test_bmi_with_tall_height(): void
    {
        $user = User::factory()->create();

        $measurement = BodyMeasurement::create([
            'user_id' => $user->id,
            'date' => today(),
            'weight' => 90,
            'height' => 200, // 2.0m
        ]);

        // BMI = 90 / (2.0 * 2.0) = 90 / 4.0 = 22.5
        $this->assertEquals('22.5', $measurement->bmi);
    }

    // ==========================================
    // Weight and Measurements Tests
    // ==========================================

    public function test_stores_all_measurement_fields(): void
    {
        $user = User::factory()->create();

        $measurement = BodyMeasurement::create([
            'user_id' => $user->id,
            'date' => today(),
            'weight' => 75.5,
            'height' => 175,
            'waist' => 85.5,
            'hips' => 95.0,
            'chest' => 100.5,
            'arm' => 35.0,
            'thigh' => 55.5,
            'body_fat' => 18.5,
            'notes' => 'Test measurement',
        ]);

        $measurement->refresh();

        $this->assertEquals(75.5, $measurement->weight);
        $this->assertEquals(175, $measurement->height);
        $this->assertEquals(85.5, $measurement->waist);
        $this->assertEquals(95.0, $measurement->hips);
        $this->assertEquals(100.5, $measurement->chest);
        $this->assertEquals(35.0, $measurement->arm);
        $this->assertEquals(55.5, $measurement->thigh);
        $this->assertEquals(18.5, $measurement->body_fat);
    }

    public function test_allows_partial_measurements(): void
    {
        $user = User::factory()->create();

        // Create with only weight
        $measurement = BodyMeasurement::create([
            'user_id' => $user->id,
            'date' => today(),
            'weight' => 75.5,
        ]);

        $measurement->refresh();

        $this->assertEquals(75.5, $measurement->weight);
        $this->assertNull($measurement->waist);
        $this->assertNull($measurement->chest);
    }

    // ==========================================
    // Date Uniqueness Tests
    // ==========================================

    public function test_enforces_unique_user_date_constraint(): void
    {
        $user = User::factory()->create();

        BodyMeasurement::create([
            'user_id' => $user->id,
            'date' => today(),
            'weight' => 75,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        BodyMeasurement::create([
            'user_id' => $user->id,
            'date' => today(),
            'weight' => 76,
        ]);
    }

    public function test_allows_same_date_for_different_users(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $m1 = BodyMeasurement::create([
            'user_id' => $user1->id,
            'date' => today(),
            'weight' => 75,
        ]);

        $m2 = BodyMeasurement::create([
            'user_id' => $user2->id,
            'date' => today(),
            'weight' => 80,
        ]);

        $this->assertEquals(75, $m1->weight);
        $this->assertEquals(80, $m2->weight);
    }

    // ==========================================
    // User Relationship Tests
    // ==========================================

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create(['name' => 'Test User']);

        $measurement = BodyMeasurement::create([
            'user_id' => $user->id,
            'date' => today(),
            'weight' => 75,
        ]);

        $this->assertEquals('Test User', $measurement->user->name);
    }
}
