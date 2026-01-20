<?php

namespace Tests\Unit\Models;

use App\Models\Booking;
use App\Models\BookingReschedule;
use App\Models\GymClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingRescheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_booking_reschedule(): void
    {
        $reschedule = BookingReschedule::factory()->create();

        $this->assertDatabaseHas('booking_reschedules', [
            'id' => $reschedule->id,
            'booking_id' => $reschedule->booking_id,
        ]);
    }

    public function test_belongs_to_booking(): void
    {
        $booking = Booking::factory()->create();
        $reschedule = BookingReschedule::factory()->create(['booking_id' => $booking->id]);

        $this->assertInstanceOf(Booking::class, $reschedule->booking);
        $this->assertEquals($booking->id, $reschedule->booking->id);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $reschedule = BookingReschedule::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $reschedule->user);
        $this->assertEquals($user->id, $reschedule->user->id);
    }

    public function test_belongs_to_original_class(): void
    {
        $gymClass = GymClass::factory()->create();
        $reschedule = BookingReschedule::factory()->create(['original_class_id' => $gymClass->id]);

        $this->assertInstanceOf(GymClass::class, $reschedule->originalClass);
        $this->assertEquals($gymClass->id, $reschedule->originalClass->id);
    }

    public function test_belongs_to_new_class(): void
    {
        $gymClass = GymClass::factory()->create();
        $reschedule = BookingReschedule::factory()->create(['new_class_id' => $gymClass->id]);

        $this->assertInstanceOf(GymClass::class, $reschedule->newClass);
        $this->assertEquals($gymClass->id, $reschedule->newClass->id);
    }

    public function test_belongs_to_processed_by_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $reschedule = BookingReschedule::factory()->create([
            'processed_by' => $admin->id,
            'status' => 'approved',
        ]);

        $this->assertInstanceOf(User::class, $reschedule->processedByUser);
        $this->assertEquals($admin->id, $reschedule->processedByUser->id);
    }

    public function test_datetime_casts(): void
    {
        $reschedule = BookingReschedule::factory()->create();

        $this->assertInstanceOf(\Carbon\Carbon::class, $reschedule->original_datetime);
        $this->assertInstanceOf(\Carbon\Carbon::class, $reschedule->new_datetime);
        $this->assertInstanceOf(\Carbon\Carbon::class, $reschedule->requested_at);
    }

    public function test_pending_scope(): void
    {
        BookingReschedule::factory()->pending()->count(2)->create();
        BookingReschedule::factory()->approved()->create();

        $pending = BookingReschedule::pending()->get();

        $this->assertCount(2, $pending);
    }

    public function test_approved_scope(): void
    {
        BookingReschedule::factory()->pending()->create();
        BookingReschedule::factory()->approved()->count(2)->create();

        $approved = BookingReschedule::approved()->get();

        $this->assertCount(2, $approved);
    }

    public function test_for_user_scope(): void
    {
        $user = User::factory()->create();
        BookingReschedule::factory()->count(2)->create(['user_id' => $user->id]);
        BookingReschedule::factory()->create();

        $userReschedules = BookingReschedule::forUser($user->id)->get();

        $this->assertCount(2, $userReschedules);
    }

    public function test_pending_state(): void
    {
        $reschedule = BookingReschedule::factory()->pending()->create();

        $this->assertEquals('pending', $reschedule->status);
    }

    public function test_approved_state(): void
    {
        $reschedule = BookingReschedule::factory()->approved()->create();

        $this->assertEquals('approved', $reschedule->status);
        $this->assertNotNull($reschedule->processed_at);
        $this->assertNotNull($reschedule->processed_by);
    }

    public function test_rejected_state(): void
    {
        $reschedule = BookingReschedule::factory()->rejected()->create();

        $this->assertEquals('rejected', $reschedule->status);
        $this->assertNotNull($reschedule->processed_at);
        $this->assertNotNull($reschedule->processed_by);
    }

    public function test_get_reschedule_count_for_month(): void
    {
        $user = User::factory()->create();
        
        // Create approved reschedules for current month
        BookingReschedule::factory()->approved()->count(3)->create([
            'user_id' => $user->id,
            'requested_at' => now(),
        ]);
        
        // Create pending reschedule (should not count)
        BookingReschedule::factory()->pending()->create([
            'user_id' => $user->id,
            'requested_at' => now(),
        ]);

        $count = BookingReschedule::getRescheduleCountForMonth($user->id);

        $this->assertEquals(3, $count);
    }
}
