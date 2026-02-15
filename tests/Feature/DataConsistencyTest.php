<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use App\Models\User;
use App\Models\Booking;
use App\Models\GymClass;
use App\Models\Store;
use App\Models\UserPackage;
use App\Models\ClassWaitlist;
use App\Events\BookingCancelled;
use App\Services\BookingCompletionService;
use App\Exceptions\BusinessValidationException;

class DataConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private function createTestStore(): Store
    {
        return Store::create([
            'name' => 'Test Store',
            'address' => '123 Test St',
        ]);
    }

    private function createTestUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'member',
        ], $overrides));
    }

    public function test_booking_delete_decrements_class_participants(): void
    {
        $store = $this->createTestStore();
        $user = $this->createTestUser();

        $gymClass = GymClass::create([
            'name' => 'Test Class',
            'type' => 'group',
            'instructor' => 'Trainer',
            'date' => now()->addDay()->toDateString(),
            'time' => '10:00',
            'duration' => 60,
            'max_participants' => 10,
            'current_participants' => 3,
            'location' => 'Room A',
            'description' => 'Test class',
        ]);

        $booking = Booking::create([
            'user_id' => $user->id,
            'store_id' => $store->id,
            'class_id' => $gymClass->id,
            'class_name' => $gymClass->name,
            'instructor' => 'Trainer',
            'date' => $gymClass->date,
            'time' => $gymClass->time,
            'type' => 'group',
            'status' => 'confirmed',
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'location' => 'Room A',
            'booking_time' => now(),
        ]);

        Event::fake([BookingCancelled::class]);

        $token = $user->createToken('test')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/v1/bookings/{$booking->id}");

        $this->assertEquals(2, $gymClass->fresh()->current_participants);
    }

    public function test_booking_delete_fires_cancellation_event(): void
    {
        Event::fake([BookingCancelled::class]);

        $store = $this->createTestStore();
        $user = $this->createTestUser();

        $booking = Booking::create([
            'user_id' => $user->id,
            'store_id' => $store->id,
            'class_name' => 'Test Class',
            'instructor' => 'Trainer',
            'date' => now()->addDay()->toDateString(),
            'time' => '10:00',
            'type' => 'personal',
            'status' => 'confirmed',
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'location' => 'Test Store',
            'booking_time' => now(),
        ]);

        $token = $user->createToken('test')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/v1/bookings/{$booking->id}");

        Event::assertDispatched(BookingCancelled::class);
    }

    public function test_waitlist_position_sequential(): void
    {
        $gymClass = GymClass::create([
            'name' => 'Full Class',
            'type' => 'group',
            'instructor' => 'Trainer',
            'date' => now()->addDay()->toDateString(),
            'time' => '10:00',
            'duration' => 60,
            'max_participants' => 1,
            'current_participants' => 1,
            'location' => 'Room A',
            'description' => 'Full class',
        ]);

        $positions = [];
        for ($i = 1; $i <= 3; $i++) {
            $user = $this->createTestUser();
            $lastPos = ClassWaitlist::where('class_id', $gymClass->id)->max('position') ?? 0;

            $waitlist = ClassWaitlist::create([
                'class_id' => $gymClass->id,
                'user_id' => $user->id,
                'position' => $lastPos + 1,
                'status' => 'waiting',
            ]);

            $positions[] = $waitlist->position;
        }

        $this->assertEquals([1, 2, 3], $positions);
    }

    public function test_cancelled_booking_does_not_decrement_participants_again(): void
    {
        $gymClass = GymClass::create([
            'name' => 'Test Class',
            'type' => 'group',
            'instructor' => 'Trainer',
            'date' => now()->addDay()->toDateString(),
            'time' => '10:00',
            'duration' => 60,
            'max_participants' => 10,
            'current_participants' => 5,
            'location' => 'Room A',
            'description' => 'Test class',
        ]);

        $store = $this->createTestStore();
        $user = $this->createTestUser();

        $booking = Booking::create([
            'user_id' => $user->id,
            'store_id' => $store->id,
            'class_id' => $gymClass->id,
            'class_name' => $gymClass->name,
            'instructor' => 'Trainer',
            'date' => $gymClass->date,
            'time' => $gymClass->time,
            'type' => 'group',
            'status' => 'cancelled',
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'location' => 'Room A',
            'booking_time' => now(),
        ]);

        Event::fake([BookingCancelled::class]);

        $token = $user->createToken('test')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/v1/bookings/{$booking->id}");

        // Participants should NOT change since booking was already cancelled
        $this->assertEquals(5, $gymClass->fresh()->current_participants);
    }

    public function test_booking_completion_validates_store_assignment(): void
    {
        $user = $this->createTestUser();

        $booking = Booking::create([
            'user_id' => $user->id,
            'store_id' => null,
            'class_name' => 'Test Class',
            'instructor' => 'Trainer',
            'date' => now()->toDateString(),
            'time' => '10:00',
            'type' => 'personal',
            'status' => 'confirmed',
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'location' => 'Test Store',
            'booking_time' => now(),
        ]);

        $this->expectException(BusinessValidationException::class);

        $service = app(BookingCompletionService::class);
        $service->completeBooking($booking->id, $user->id);
    }

    public function test_booking_completion_rejects_already_completed(): void
    {
        $store = $this->createTestStore();
        $user = $this->createTestUser();

        $booking = Booking::create([
            'user_id' => $user->id,
            'store_id' => $store->id,
            'class_name' => 'Test Class',
            'instructor' => 'Trainer',
            'date' => now()->toDateString(),
            'time' => '10:00',
            'type' => 'personal',
            'status' => 'completed',
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'location' => 'Test Store',
            'booking_time' => now(),
        ]);

        $this->expectException(BusinessValidationException::class);

        $service = app(BookingCompletionService::class);
        $service->completeBooking($booking->id, $user->id);
    }

    public function test_booking_completion_rejects_cancelled_booking(): void
    {
        $store = $this->createTestStore();
        $user = $this->createTestUser();

        $booking = Booking::create([
            'user_id' => $user->id,
            'store_id' => $store->id,
            'class_name' => 'Test Class',
            'instructor' => 'Trainer',
            'date' => now()->toDateString(),
            'time' => '10:00',
            'type' => 'personal',
            'status' => 'cancelled',
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'location' => 'Test Store',
            'booking_time' => now(),
        ]);

        $this->expectException(BusinessValidationException::class);

        $service = app(BookingCompletionService::class);
        $service->completeBooking($booking->id, $user->id);
    }
}
