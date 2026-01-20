<?php

namespace Tests\Unit\Models;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_has_fillable_attributes(): void
    {
        $booking = new Booking();
        $fillable = $booking->getFillable();

        $this->assertContains('user_id', $fillable);
        $this->assertContains('customer_name', $fillable);
        $this->assertContains('class_name', $fillable);
        $this->assertContains('date', $fillable);
        $this->assertContains('status', $fillable);
    }

    public function test_booking_casts_date_correctly(): void
    {
        $booking = new Booking();
        $casts = $booking->getCasts();

        $this->assertEquals('date:Y-m-d', $casts['date']);
        $this->assertEquals('boolean', $casts['is_priority_booking']);
        $this->assertEquals('boolean', $casts['absence_with_charge']);
    }

    public function test_booking_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $booking->user);
        $this->assertEquals($user->id, $booking->user->id);
    }

    public function test_booking_has_instructor_name_accessor(): void
    {
        $booking = new Booking(['instructor' => 'John Doe']);
        $this->assertEquals('John Doe', $booking->instructor_name);

        $bookingNoInstructor = new Booking();
        $this->assertEquals('N/A', $bookingNoInstructor->instructor_name);
    }

    public function test_booking_has_checked_in_accessor(): void
    {
        $booking = new Booking(['attended' => true]);
        $this->assertTrue($booking->checked_in);

        $bookingNotAttended = new Booking(['attended' => false]);
        $this->assertFalse($bookingNotAttended->checked_in);
    }

    public function test_booking_has_is_waitlist_accessor(): void
    {
        $booking = new Booking(['status' => 'waitlist']);
        $this->assertTrue($booking->is_waitlist);

        $bookingConfirmed = new Booking(['status' => 'confirmed']);
        $this->assertFalse($bookingConfirmed->is_waitlist);
    }

    public function test_booking_can_be_created_with_factory(): void
    {
        $booking = Booking::factory()->create();

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertDatabaseHas('bookings', ['id' => $booking->id]);
    }
}
