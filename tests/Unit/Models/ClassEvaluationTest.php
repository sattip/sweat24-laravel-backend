<?php

namespace Tests\Unit\Models;

use App\Models\Booking;
use App\Models\ClassEvaluation;
use App\Models\GymClass;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassEvaluationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_class_evaluation(): void
    {
        $evaluation = ClassEvaluation::factory()->create();
        $this->assertDatabaseHas('class_evaluations', ['id' => $evaluation->id]);
    }

    public function test_belongs_to_class(): void
    {
        $class = GymClass::factory()->create();
        $evaluation = ClassEvaluation::factory()->create(['class_id' => $class->id]);

        $this->assertInstanceOf(GymClass::class, $evaluation->gymClass);
        $this->assertEquals($class->id, $evaluation->gymClass->id);
    }

    public function test_belongs_to_booking(): void
    {
        $booking = Booking::factory()->create();
        $evaluation = ClassEvaluation::factory()->create(['booking_id' => $booking->id]);

        $this->assertInstanceOf(Booking::class, $evaluation->booking);
        $this->assertEquals($booking->id, $evaluation->booking->id);
    }

    public function test_excellent_state(): void
    {
        $evaluation = ClassEvaluation::factory()->excellent()->create();

        $this->assertEquals(5, $evaluation->overall_rating);
        $this->assertEquals(5, $evaluation->instructor_rating);
        $this->assertEquals(5, $evaluation->facility_rating);
        $this->assertTrue($evaluation->would_recommend);
    }

    public function test_poor_state(): void
    {
        $evaluation = ClassEvaluation::factory()->poor()->create();

        $this->assertEquals(1, $evaluation->overall_rating);
        $this->assertEquals(1, $evaluation->instructor_rating);
        $this->assertEquals(1, $evaluation->facility_rating);
        $this->assertFalse($evaluation->would_recommend);
    }

    public function test_submitted_state(): void
    {
        $evaluation = ClassEvaluation::factory()->submitted()->create();

        $this->assertTrue($evaluation->is_submitted);
        $this->assertNotNull($evaluation->submitted_at);
    }

    public function test_ratings_are_valid_range(): void
    {
        $evaluation = ClassEvaluation::factory()->create();

        $this->assertGreaterThanOrEqual(1, $evaluation->overall_rating);
        $this->assertLessThanOrEqual(5, $evaluation->overall_rating);
        $this->assertGreaterThanOrEqual(1, $evaluation->instructor_rating);
        $this->assertLessThanOrEqual(5, $evaluation->instructor_rating);
        $this->assertGreaterThanOrEqual(1, $evaluation->facility_rating);
        $this->assertLessThanOrEqual(5, $evaluation->facility_rating);
    }

    public function test_has_unique_evaluation_token(): void
    {
        $eval1 = ClassEvaluation::factory()->create();
        $eval2 = ClassEvaluation::factory()->create();

        $this->assertNotEquals($eval1->evaluation_token, $eval2->evaluation_token);
    }
}
