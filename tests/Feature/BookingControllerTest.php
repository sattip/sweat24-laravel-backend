<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Booking;
use App\Models\GymClass;
use App\Models\Instructor;
use App\Models\Package;
use App\Models\UserPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class BookingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $admin;
    protected $gymClass;
    protected $package;
    protected $userPackage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'member']);
        $this->admin = User::factory()->create(['role' => 'admin']);

        $instructor = Instructor::factory()->create();

        $this->gymClass = GymClass::factory()->create([
            'instructor' => $instructor->id,
            'date' => now()->addDays(1)->format('Y-m-d'),
            'time' => '10:00:00',
            'max_participants' => 20,
            'current_participants' => 5,
            'status' => 'active'
        ]);

        $this->package = Package::factory()->create([
            'name' => 'Test Package',
        ]);

        $this->userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'remaining_sessions' => 10,
            'status' => 'active',
            'expiry_date' => now()->addDays(30)
        ]);
    }

    public function test_user_can_book_class_with_available_sessions()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/bookings', [
            'class_id' => $this->gymClass->id,
            'booking_date' => now()->addDays(1)->format('Y-m-d'),
            'duration' => 1
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'user_id',
                'class_id',
                'booking_date',
                'status'
            ]);

        $this->assertDatabaseHas('bookings', [
            'user_id' => $this->user->id,
            'class_id' => $this->gymClass->id,
            'status' => 'confirmed'
        ]);

        // Check that session was deducted
        $this->userPackage->refresh();
        $this->assertEquals(9, $this->userPackage->remaining_sessions);
    }

    public function test_user_cannot_book_class_without_sessions()
    {
        $this->userPackage->update(['remaining_sessions' => 0]);
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/bookings', [
            'class_id' => $this->gymClass->id,
            'booking_date' => now()->addDays(1)->format('Y-m-d'),
            'duration' => 1
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'No active package or insufficient sessions'
            ]);
    }

    public function test_user_cannot_book_full_class()
    {
        $this->gymClass->update(['current_participants' => 20]);
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/bookings', [
            'class_id' => $this->gymClass->id,
            'booking_date' => now()->addDays(1)->format('Y-m-d'),
            'duration' => 1
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Class is full'
            ]);
    }

    public function test_user_cannot_double_book_same_class()
    {
        Sanctum::actingAs($this->user);

        // First booking using factory
        Booking::factory()->create([
            'user_id' => $this->user->id,
            'class_id' => $this->gymClass->id,
            'date' => now()->addDays(1),
            'status' => 'confirmed'
        ]);

        // Try to book again
        $response = $this->postJson('/api/v1/bookings', [
            'class_id' => $this->gymClass->id,
            'booking_date' => now()->addDays(1)->format('Y-m-d'),
            'duration' => 1
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'You have already booked this class'
            ]);
    }

    public function test_user_can_view_own_bookings()
    {
        Sanctum::actingAs($this->user);

        Booking::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'class_id' => $this->gymClass->id
        ]);

        $response = $this->getJson('/api/v1/bookings');

        $response->assertStatus(200)
            ->assertJsonCount(3)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'user_id',
                    'class_id',
                    'date',
                    'status'
                ]
            ]);
    }

    public function test_admin_can_view_all_bookings()
    {
        Sanctum::actingAs($this->admin);

        Booking::factory()->count(5)->create([
            'class_id' => $this->gymClass->id
        ]);

        $response = $this->getJson('/api/v1/bookings');

        $response->assertStatus(200)
            ->assertJsonCount(5);
    }

    public function test_user_can_cancel_own_booking()
    {
        Sanctum::actingAs($this->user);

        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'class_id' => $this->gymClass->id,
            'date' => now()->addDays(2),
            'status' => 'confirmed'
        ]);

        // Deduct session first
        $this->userPackage->decrement('remaining_sessions');

        $response = $this->putJson("/api/v1/bookings/{$booking->id}", [
            'status' => 'cancelled'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'cancelled'
            ]);

        // Check that session was refunded
        $this->userPackage->refresh();
        $this->assertEquals(10, $this->userPackage->remaining_sessions);
    }

    public function test_user_cannot_cancel_past_booking()
    {
        Sanctum::actingAs($this->user);

        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'class_id' => $this->gymClass->id,
            'date' => now()->subDays(1),
            'status' => 'confirmed'
        ]);

        $response = $this->putJson("/api/v1/bookings/{$booking->id}", [
            'status' => 'cancelled'
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Cannot cancel past bookings'
            ]);
    }

    public function test_user_cannot_cancel_other_user_booking()
    {
        Sanctum::actingAs($this->user);
        $otherUser = User::factory()->create();

        $booking = Booking::factory()->create([
            'user_id' => $otherUser->id,
            'class_id' => $this->gymClass->id,
            'date' => now()->addDays(2),
            'status' => 'confirmed'
        ]);

        $response = $this->putJson("/api/v1/bookings/{$booking->id}", [
            'status' => 'cancelled'
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_booking()
    {
        Sanctum::actingAs($this->admin);

        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'class_id' => $this->gymClass->id,
            'date' => now()->addDays(2),
            'status' => 'confirmed'
        ]);

        $response = $this->deleteJson("/api/v1/bookings/{$booking->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('bookings', [
            'id' => $booking->id
        ]);
    }

    public function test_regular_user_cannot_delete_booking()
    {
        Sanctum::actingAs($this->user);

        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'class_id' => $this->gymClass->id,
            'date' => now()->addDays(2),
            'status' => 'confirmed'
        ]);

        $response = $this->deleteJson("/api/v1/bookings/{$booking->id}");

        $response->assertStatus(403);
    }
}
