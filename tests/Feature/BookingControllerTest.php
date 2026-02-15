<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Booking;
use App\Models\GymClass;
use App\Models\Instructor;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Events\BookingCancelled;
use Laravel\Sanctum\Sanctum;

class BookingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $admin;
    protected $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'member']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->store = Store::create([
            'name' => 'Test Store',
            'address' => '123 Test St',
        ]);
    }

    private function createBooking(array $overrides = []): Booking
    {
        return Booking::create(array_merge([
            'user_id' => $this->user->id,
            'store_id' => $this->store->id,
            'class_name' => 'Test Class',
            'instructor' => 'Test Trainer',
            'date' => now()->addDays(2)->toDateString(),
            'time' => '10:00',
            'type' => 'personal',
            'status' => 'confirmed',
            'customer_name' => $this->user->name,
            'customer_email' => $this->user->email,
            'location' => 'Test Store',
            'booking_time' => now(),
        ], $overrides));
    }

    public function test_authenticated_user_can_view_own_booking()
    {
        Sanctum::actingAs($this->user);
        $booking = $this->createBooking();

        $response = $this->getJson("/api/v1/bookings/{$booking->id}");

        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_cannot_view_booking()
    {
        $booking = $this->createBooking();

        $response = $this->getJson("/api/v1/bookings/{$booking->id}");

        $response->assertStatus(401);
    }

    public function test_user_can_cancel_own_booking()
    {
        Event::fake([BookingCancelled::class]);
        Sanctum::actingAs($this->user);

        $booking = $this->createBooking([
            'date' => now()->addDays(2)->toDateString(),
        ]);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/cancel");

        $response->assertSuccessful();

        $booking->refresh();
        $this->assertEquals('cancelled', $booking->status);
    }

    public function test_user_can_delete_own_booking()
    {
        Event::fake([BookingCancelled::class]);
        Sanctum::actingAs($this->user);

        $booking = $this->createBooking([
            'date' => now()->addDays(2)->toDateString(),
        ]);

        $response = $this->deleteJson("/api/v1/bookings/{$booking->id}");

        $response->assertSuccessful();
    }

    public function test_booking_cancel_fires_event()
    {
        Event::fake([BookingCancelled::class]);
        Sanctum::actingAs($this->user);

        $booking = $this->createBooking([
            'date' => now()->addDays(2)->toDateString(),
        ]);

        $this->postJson("/api/v1/bookings/{$booking->id}/cancel");

        Event::assertDispatched(BookingCancelled::class);
    }

    public function test_group_booking_decrements_participants_on_cancel()
    {
        Event::fake([BookingCancelled::class]);

        $instructor = Instructor::create([
            'name' => 'Test Instructor',
            'email' => 'instructor@test.com',
            'phone' => '1234567890',
            'specialties' => ['Yoga'],
            'hourly_rate' => 20,
            'contract_type' => 'hourly',
            'join_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $gymClass = GymClass::create([
            'name' => 'Test Class',
            'type' => 'group',
            'instructor' => $instructor->id,
            'date' => now()->addDays(2)->toDateString(),
            'time' => '10:00',
            'duration' => 60,
            'max_participants' => 10,
            'current_participants' => 5,
            'location' => 'Room A',
            'description' => 'Test class',
        ]);

        Sanctum::actingAs($this->user);

        $booking = $this->createBooking([
            'class_id' => $gymClass->id,
            'class_name' => $gymClass->name,
            'type' => 'group',
            'date' => $gymClass->date,
            'time' => $gymClass->time,
        ]);

        $this->deleteJson("/api/v1/bookings/{$booking->id}");

        $this->assertEquals(4, $gymClass->fresh()->current_participants);
    }

    public function test_cancelled_booking_not_decremented_again()
    {
        Event::fake([BookingCancelled::class]);

        $instructor = Instructor::create([
            'name' => 'Test Instructor 2',
            'email' => 'instructor2@test.com',
            'phone' => '1234567890',
            'specialties' => ['Yoga'],
            'hourly_rate' => 20,
            'contract_type' => 'hourly',
            'join_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $gymClass = GymClass::create([
            'name' => 'Test Class',
            'type' => 'group',
            'instructor' => $instructor->id,
            'date' => now()->addDays(2)->toDateString(),
            'time' => '10:00',
            'duration' => 60,
            'max_participants' => 10,
            'current_participants' => 5,
            'location' => 'Room A',
            'description' => 'Test class',
        ]);

        Sanctum::actingAs($this->user);

        // Already cancelled booking
        $booking = $this->createBooking([
            'class_id' => $gymClass->id,
            'class_name' => $gymClass->name,
            'type' => 'group',
            'date' => $gymClass->date,
            'time' => $gymClass->time,
            'status' => 'cancelled',
        ]);

        $this->deleteJson("/api/v1/bookings/{$booking->id}");

        // Participants should NOT change
        $this->assertEquals(5, $gymClass->fresh()->current_participants);
    }

    public function test_admin_can_view_bookings_history()
    {
        Sanctum::actingAs($this->admin);

        $this->createBooking();

        $response = $this->getJson('/api/v1/bookings/history');

        $response->assertStatus(200);
    }

    public function test_booking_show_requires_authentication()
    {
        $booking = $this->createBooking();

        // Show endpoint requires auth (cancel has a public route)
        $response = $this->getJson("/api/v1/bookings/{$booking->id}");

        $response->assertStatus(401);
    }
}
