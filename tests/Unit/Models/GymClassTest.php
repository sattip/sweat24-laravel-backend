<?php

namespace Tests\Unit\Models;

use App\Models\Booking;
use App\Models\GymClass;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GymClassTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_gym_class(): void
    {
        $gymClass = GymClass::factory()->create();

        $this->assertDatabaseHas('gym_classes', [
            'id' => $gymClass->id,
            'name' => $gymClass->name,
        ]);
    }

    public function test_instructor_relationship_exists(): void
    {
        $gymClass = GymClass::factory()->create();

        // The model has instructor relationship but table stores instructor name as string
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $gymClass->instructor());
    }

    public function test_service_relationship_exists(): void
    {
        $gymClass = GymClass::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $gymClass->service());
    }

    public function test_cancellation_policy_relationship_exists(): void
    {
        $gymClass = GymClass::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $gymClass->cancellationPolicy());
    }

    public function test_has_many_bookings(): void
    {
        $gymClass = GymClass::factory()->create();
        Booking::factory()->count(3)->create(['class_id' => $gymClass->id]);

        $this->assertCount(3, $gymClass->bookings);
        $this->assertInstanceOf(Booking::class, $gymClass->bookings->first());
    }

    public function test_waitlist_relationship_exists(): void
    {
        $gymClass = GymClass::factory()->create();

        // Just verify the relationship method exists and returns a relation
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $gymClass->waitlist());
    }

    public function test_evaluations_relationship_exists(): void
    {
        $gymClass = GymClass::factory()->create();

        // Just verify the relationship method exists and returns a relation
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $gymClass->evaluations());
    }

    public function test_is_full_returns_true_when_full(): void
    {
        $gymClass = GymClass::factory()->create([
            'max_participants' => 10,
            'current_participants' => 10,
        ]);

        $this->assertTrue($gymClass->isFull());
    }

    public function test_is_full_returns_false_when_not_full(): void
    {
        $gymClass = GymClass::factory()->create([
            'max_participants' => 10,
            'current_participants' => 5,
        ]);

        $this->assertFalse($gymClass->isFull());
    }

    public function test_has_available_spots(): void
    {
        $gymClass = GymClass::factory()->create([
            'max_participants' => 10,
            'current_participants' => 5,
        ]);

        $this->assertTrue($gymClass->hasAvailableSpots());
    }

    public function test_available_spots(): void
    {
        $gymClass = GymClass::factory()->create([
            'max_participants' => 10,
            'current_participants' => 7,
        ]);

        $this->assertEquals(3, $gymClass->availableSpots());
    }

    public function test_available_spots_returns_zero_when_full(): void
    {
        $gymClass = GymClass::factory()->create([
            'max_participants' => 10,
            'current_participants' => 10,
        ]);

        $this->assertEquals(0, $gymClass->availableSpots());
    }

    public function test_date_cast(): void
    {
        $gymClass = GymClass::factory()->create(['date' => '2026-01-15']);

        $this->assertInstanceOf(\Carbon\Carbon::class, $gymClass->date);
        $this->assertEquals('2026-01-15', $gymClass->date->format('Y-m-d'));
    }

    public function test_priority_booking_enabled_cast_to_boolean(): void
    {
        $gymClass = GymClass::factory()->create(['priority_booking_enabled' => true]);

        $this->assertIsBool($gymClass->priority_booking_enabled);
    }

    public function test_has_priority_seats_available(): void
    {
        $gymClass = GymClass::factory()->create([
            'priority_booking_enabled' => true,
            'priority_seats' => 5,
            'priority_seats_booked' => 2,
        ]);

        $this->assertTrue($gymClass->hasPrioritySeatsAvailable());
    }

    public function test_has_no_priority_seats_available_when_full(): void
    {
        $gymClass = GymClass::factory()->create([
            'priority_booking_enabled' => true,
            'priority_seats' => 5,
            'priority_seats_booked' => 5,
        ]);

        $this->assertFalse($gymClass->hasPrioritySeatsAvailable());
    }

    public function test_has_no_priority_seats_when_disabled(): void
    {
        $gymClass = GymClass::factory()->create([
            'priority_booking_enabled' => false,
            'priority_seats' => 5,
            'priority_seats_booked' => 2,
        ]);

        $this->assertFalse($gymClass->hasPrioritySeatsAvailable());
    }

    public function test_available_priority_seats(): void
    {
        $gymClass = GymClass::factory()->create([
            'priority_seats' => 5,
            'priority_seats_booked' => 2,
        ]);

        $this->assertEquals(3, $gymClass->availablePrioritySeats());
    }

    public function test_available_regular_seats(): void
    {
        $gymClass = GymClass::factory()->create([
            'max_participants' => 20,
            'current_participants' => 10,
            'priority_seats' => 5,
            'priority_seats_booked' => 3,
        ]);

        // Regular seats total = 20 - 5 = 15
        // Regular seats booked = 10 - 3 = 7
        // Available = 15 - 7 = 8
        $this->assertEquals(8, $gymClass->availableRegularSeats());
    }

    public function test_get_availability_info(): void
    {
        // Use explicit date/time to avoid parsing issues
        $gymClass = GymClass::factory()->create([
            'date' => '2026-01-15',
            'time' => '14:00',
            'max_participants' => 20,
            'current_participants' => 10,
            'priority_seats' => 5,
            'priority_seats_booked' => 3,
            'priority_booking_enabled' => true,
            'priority_seats_release_at' => now()->addDay(), // Set release time in future
        ]);

        $info = $gymClass->getAvailabilityInfo();

        $this->assertArrayHasKey('total_capacity', $info);
        $this->assertArrayHasKey('current_participants', $info);
        $this->assertArrayHasKey('priority_seats', $info);
        $this->assertArrayHasKey('priority_seats_available', $info);
        $this->assertArrayHasKey('regular_seats_available', $info);
        $this->assertArrayHasKey('total_available', $info);
    }

    public function test_book_priority_seat(): void
    {
        $gymClass = GymClass::factory()->create([
            'priority_booking_enabled' => true,
            'priority_seats' => 5,
            'priority_seats_booked' => 2,
            'current_participants' => 5,
        ]);

        $result = $gymClass->bookPrioritySeat();

        $this->assertTrue($result);
        $this->assertEquals(3, $gymClass->fresh()->priority_seats_booked);
        $this->assertEquals(6, $gymClass->fresh()->current_participants);
    }

    public function test_book_priority_seat_fails_when_full(): void
    {
        $gymClass = GymClass::factory()->create([
            'priority_booking_enabled' => true,
            'priority_seats' => 5,
            'priority_seats_booked' => 5,
        ]);

        $result = $gymClass->bookPrioritySeat();

        $this->assertFalse($result);
    }

    public function test_cancel_priority_booking(): void
    {
        $gymClass = GymClass::factory()->create([
            'priority_seats_booked' => 3,
            'current_participants' => 10,
        ]);

        $gymClass->cancelPriorityBooking();

        $this->assertEquals(2, $gymClass->fresh()->priority_seats_booked);
        $this->assertEquals(9, $gymClass->fresh()->current_participants);
    }

    public function test_time_accessor_formats_long_time(): void
    {
        $gymClass = GymClass::factory()->create(['time' => '2026-01-15 14:30:00']);

        $this->assertEquals('14:30', $gymClass->time);
    }

    public function test_time_accessor_keeps_short_time(): void
    {
        $gymClass = GymClass::factory()->create(['time' => '14:30']);

        $this->assertEquals('14:30', $gymClass->time);
    }
}
