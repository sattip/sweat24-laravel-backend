<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Booking;
use App\Models\Instructor;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class BookingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;
    protected Store $store;
    protected Instructor $instructor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $this->admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->store = Store::factory()->create();
        $this->instructor = Instructor::factory()->create();
    }

    public function test_admin_can_view_all_bookings()
    {
        Sanctum::actingAs($this->admin);

        Booking::factory()->count(3)->create([
            'store_id' => $this->store->id
        ]);

        $response = $this->getJson('/api/v1/bookings');

        $response->assertStatus(200);
        // Admin should get bookings as array
        $this->assertIsArray($response->json());
    }

    public function test_admin_can_create_booking()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/bookings', [
            'store_id' => $this->store->id,
            'user_id' => $this->user->id,
            'class_name' => 'Yoga Class',
            'instructor' => $this->instructor->name,
            'date' => now()->addDays(1)->format('Y-m-d'),
            'time' => '10:00',
            'type' => 'group',
            'location' => 'Studio A'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'booking' => [
                        'id',
                        'user_id',
                        'store_id',
                        'class_name',
                        'date',
                        'time',
                        'status'
                    ]
                ]
            ]);
    }

    public function test_admin_can_view_booking()
    {
        Sanctum::actingAs($this->admin);

        $booking = Booking::factory()->create([
            'store_id' => $this->store->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson("/api/v1/bookings/{$booking->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $booking->id
            ]);
    }

    public function test_admin_can_update_booking_status()
    {
        Sanctum::actingAs($this->admin);

        $booking = Booking::factory()->create([
            'store_id' => $this->store->id,
            'user_id' => $this->user->id,
            'status' => 'confirmed'
        ]);

        $response = $this->putJson("/api/v1/bookings/{$booking->id}", [
            'status' => 'cancelled'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled'
        ]);
    }

    public function test_admin_can_delete_booking()
    {
        Sanctum::actingAs($this->admin);

        $booking = Booking::factory()->create([
            'store_id' => $this->store->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->deleteJson("/api/v1/bookings/{$booking->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('bookings', [
            'id' => $booking->id
        ]);
    }

    public function test_booking_requires_store_id()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/bookings', [
            'class_name' => 'Yoga Class',
            'instructor' => $this->instructor->name,
            'date' => now()->addDays(1)->format('Y-m-d'),
            'time' => '10:00',
            'type' => 'group'
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['store_id']]);
    }

    public function test_booking_requires_class_name()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/bookings', [
            'store_id' => $this->store->id,
            'instructor' => $this->instructor->name,
            'date' => now()->addDays(1)->format('Y-m-d'),
            'time' => '10:00',
            'type' => 'group'
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['class_name']]);
    }

    public function test_cancel_booking()
    {
        Sanctum::actingAs($this->admin);

        $booking = Booking::factory()->create([
            'store_id' => $this->store->id,
            'user_id' => $this->user->id,
            'status' => 'confirmed',
            'date' => now()->addDays(2)->format('Y-m-d')
        ]);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/cancel");

        $response->assertStatus(200);

        $booking->refresh();
        $this->assertEquals('cancelled', $booking->status);
    }
}
