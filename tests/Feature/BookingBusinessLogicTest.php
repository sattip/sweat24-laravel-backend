<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Booking;
use App\Models\UserPackage;
use App\Models\Package;
use App\Models\Store;
use App\Models\GymClass;
use App\Services\BookingCompletionService;
use App\Exceptions\BusinessValidationException;

class BookingBusinessLogicTest extends TestCase
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
            'membership_type' => 'regular',
        ], $overrides));
    }

    private function createTestPackage(): Package
    {
        return Package::create([
            'name' => 'Test Package',
            'price' => 100,
            'sessions' => 10,
            'duration' => 30,
            'type' => 'personal',
        ]);
    }

    private function createActiveUserPackage(User $user, array $overrides = []): UserPackage
    {
        $package = $this->createTestPackage();

        return UserPackage::create(array_merge([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'name' => $package->name,
            'status' => 'active',
            'remaining_sessions' => 10,
            'total_sessions' => 10,
            'is_frozen' => false,
            'assigned_date' => now()->subDays(5)->toDateString(),
            'expiry_date' => now()->addDays(25)->toDateString(),
        ], $overrides));
    }

    public function test_booking_completion_does_not_double_deduct_sessions(): void
    {
        $store = $this->createTestStore();
        $user = $this->createTestUser();
        $userPackage = $this->createActiveUserPackage($user);

        $booking = Booking::create([
            'user_id' => $user->id,
            'store_id' => $store->id,
            'class_name' => 'Test Class',
            'instructor' => 'Test Trainer',
            'date' => now()->toDateString(),
            'time' => '10:00',
            'type' => 'personal',
            'status' => 'confirmed',
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'location' => 'Test Store',
            'booking_time' => now(),
        ]);

        $service = app(BookingCompletionService::class);
        $result = $service->completeBooking($booking->id, $user->id);

        // Session was NOT deducted by completeBooking (it's handled by BookingCreated event)
        $userPackage->refresh();
        $this->assertEquals(10, $userPackage->remaining_sessions);
        $this->assertEquals('completed', $result['booking']->status);
    }

    public function test_session_deduction_is_atomic(): void
    {
        $user = $this->createTestUser();
        $pkg = $this->createActiveUserPackage($user, ['remaining_sessions' => 1]);

        // First atomic decrement should succeed
        $updated = UserPackage::where('id', $pkg->id)
            ->where('remaining_sessions', '>', 0)
            ->decrement('remaining_sessions');

        $this->assertEquals(1, $updated);
        $this->assertEquals(0, $pkg->fresh()->remaining_sessions);

        // Second atomic decrement should fail (no rows affected)
        $updated2 = UserPackage::where('id', $pkg->id)
            ->where('remaining_sessions', '>', 0)
            ->decrement('remaining_sessions');

        $this->assertEquals(0, $updated2);
        $this->assertEquals(0, $pkg->fresh()->remaining_sessions);
    }

    public function test_session_refund_capped_at_total_sessions(): void
    {
        $user = $this->createTestUser();
        $pkg = $this->createActiveUserPackage($user, [
            'remaining_sessions' => 10,
            'total_sessions' => 10,
        ]);

        // Refund should be blocked when remaining = total
        $updated = UserPackage::where('id', $pkg->id)
            ->whereColumn('remaining_sessions', '<', 'total_sessions')
            ->increment('remaining_sessions');

        $this->assertEquals(0, $updated);
        $this->assertEquals(10, $pkg->fresh()->remaining_sessions);
    }

    public function test_frozen_package_not_used_for_deduction(): void
    {
        $user = $this->createTestUser();
        $pkg = $this->createActiveUserPackage($user, [
            'is_frozen' => true,
            'remaining_sessions' => 5,
        ]);

        // Simulate what ProcessSessionDeduction does - look for active non-frozen package
        $activePackage = UserPackage::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('is_frozen', false)
            ->where('remaining_sessions', '>', 0)
            ->first();

        $this->assertNull($activePackage);
        $this->assertEquals(5, $pkg->fresh()->remaining_sessions);
    }

    public function test_expired_package_not_used_for_deduction(): void
    {
        $user = $this->createTestUser();
        $pkg = $this->createActiveUserPackage($user, [
            'expiry_date' => now()->subDay()->toDateString(),
            'remaining_sessions' => 5,
        ]);

        $activePackage = UserPackage::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('is_frozen', false)
            ->where(function ($query) {
                $query->whereNull('expiry_date')
                      ->orWhere('expiry_date', '>=', now()->toDateString());
            })
            ->where('remaining_sessions', '>', 0)
            ->first();

        $this->assertNull($activePackage);
        $this->assertEquals(5, $pkg->fresh()->remaining_sessions);
    }

    public function test_guest_user_booking_completion_skips_package(): void
    {
        $store = $this->createTestStore();
        $user = $this->createTestUser(['membership_type' => 'guest']);

        $booking = Booking::create([
            'user_id' => $user->id,
            'store_id' => $store->id,
            'class_name' => 'Trial Class',
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

        $service = app(BookingCompletionService::class);
        $result = $service->completeBooking($booking->id, $user->id);

        $this->assertTrue($result['is_guest']);
        $this->assertNull($result['user_package']);
        $this->assertEquals('completed', $result['booking']->status);
    }

    public function test_booking_cancel_triggers_refund_event(): void
    {
        \Illuminate\Support\Facades\Event::fake([\App\Events\BookingCancelled::class]);

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

        $this->postJson("/api/v1/bookings/{$booking->id}/cancel", [
            'user_id' => $user->id,
        ]);

        \Illuminate\Support\Facades\Event::assertDispatched(\App\Events\BookingCancelled::class);
    }
}
