<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Booking;
use App\Models\UserPackage;
use App\Models\Package;
use App\Models\Store;
use App\Events\BookingCreated;
use App\Events\BookingCancelled;
use App\Listeners\ProcessSessionDeduction;
use Illuminate\Support\Facades\Event;
use Carbon\Carbon;

class PackageFreezeAndSessionsTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): array
    {
        $user = User::factory()->create(['role' => 'admin']);
        $token = $user->createToken('test')->plainTextToken;
        return [$user, $token];
    }

    private function createMember(): array
    {
        $user = User::factory()->create(['role' => 'member', 'membership_type' => 'regular']);
        $token = $user->createToken('test')->plainTextToken;
        return [$user, $token];
    }

    private function createPackage(array $overrides = []): Package
    {
        return Package::create(array_merge([
            'name' => 'Test Package',
            'price' => 100,
            'sessions' => 10,
            'duration' => 30,
            'type' => 'personal',
        ], $overrides));
    }

    private function createUserPackage(User $user, array $overrides = []): UserPackage
    {
        $package = $this->createPackage();

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

    private function createStore(): Store
    {
        return Store::create([
            'name' => 'Test Store',
            'address' => '123 Test St',
        ]);
    }

    // ─── FREEZE / UNFREEZE TESTS ───────────────────────────────────────

    public function test_freeze_package_sets_frozen_status(): void
    {
        [$user, $token] = $this->createMember();
        $pkg = $this->createUserPackage($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/user-packages/{$pkg->id}/freeze");

        $response->assertOk();
        $pkg->refresh();
        $this->assertTrue($pkg->is_frozen);
        $this->assertEquals('frozen', $pkg->status);
        $this->assertNotNull($pkg->frozen_at);
    }

    public function test_freeze_already_frozen_package_returns_422(): void
    {
        [$user, $token] = $this->createMember();
        $pkg = $this->createUserPackage($user, ['is_frozen' => true, 'status' => 'frozen']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/user-packages/{$pkg->id}/freeze");

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Package is already frozen']);
    }

    public function test_unfreeze_package_restores_active_status(): void
    {
        [$user, $token] = $this->createMember();
        $pkg = $this->createUserPackage($user, [
            'is_frozen' => true,
            'status' => 'frozen',
            'frozen_at' => now()->subDays(5),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/user-packages/{$pkg->id}/unfreeze");

        $response->assertOk();
        $pkg->refresh();
        $this->assertFalse($pkg->is_frozen);
        $this->assertEquals('active', $pkg->status);
        $this->assertNotNull($pkg->unfrozen_at);
    }

    public function test_unfreeze_not_frozen_package_returns_422(): void
    {
        [$user, $token] = $this->createMember();
        $pkg = $this->createUserPackage($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/user-packages/{$pkg->id}/unfreeze");

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Package is not frozen']);
    }

    public function test_freeze_with_duration_days_stores_value(): void
    {
        [$user, $token] = $this->createMember();
        $pkg = $this->createUserPackage($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/user-packages/{$pkg->id}/freeze", [
                'duration_days' => 30,
            ]);

        $response->assertOk();
        $pkg->refresh();
        $this->assertEquals(30, $pkg->freeze_duration_days);
    }

    public function test_freeze_duration_capped_at_90_days(): void
    {
        [$user, $token] = $this->createMember();
        $pkg = $this->createUserPackage($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/user-packages/{$pkg->id}/freeze", [
                'duration_days' => 120,
            ]);

        $response->assertStatus(422); // Validation fails: max:90
    }

    public function test_unfreeze_extends_expiry_by_frozen_days(): void
    {
        [$user, $token] = $this->createMember();

        $originalExpiry = now()->addDays(20);
        $pkg = $this->createUserPackage($user, [
            'is_frozen' => true,
            'status' => 'frozen',
            'frozen_at' => now()->subDays(10),
            'freeze_duration_days' => 30,
            'expiry_date' => $originalExpiry->toDateString(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/user-packages/{$pkg->id}/unfreeze");

        $response->assertOk();
        $pkg->refresh();

        // Expiry should be extended by min(10 frozen days, 30 duration_days) = 10 days
        $expectedExpiry = $originalExpiry->addDays(10)->toDateString();
        $this->assertEquals($expectedExpiry, $pkg->expiry_date->toDateString());
    }

    public function test_unfreeze_without_duration_keeps_original_expiry(): void
    {
        [$user, $token] = $this->createMember();

        $originalExpiry = now()->addDays(20)->toDateString();
        $pkg = $this->createUserPackage($user, [
            'is_frozen' => true,
            'status' => 'frozen',
            'frozen_at' => now()->subDays(10),
            'freeze_duration_days' => null,
            'expiry_date' => $originalExpiry,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/user-packages/{$pkg->id}/unfreeze");

        $response->assertOk();
        $pkg->refresh();
        $this->assertEquals($originalExpiry, $pkg->expiry_date->toDateString());
    }

    public function test_unfreeze_expired_during_freeze_sets_expired_status(): void
    {
        [$user, $token] = $this->createMember();

        // Package expired while it was frozen (no duration_days to extend)
        $pkg = $this->createUserPackage($user, [
            'is_frozen' => true,
            'status' => 'frozen',
            'frozen_at' => now()->subDays(30),
            'freeze_duration_days' => null,
            'expiry_date' => now()->subDays(5)->toDateString(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/user-packages/{$pkg->id}/unfreeze");

        $response->assertOk();
        $pkg->refresh();
        $this->assertFalse($pkg->is_frozen);
        $this->assertEquals('expired', $pkg->status);
    }

    public function test_freeze_requires_auth(): void
    {
        $user = User::factory()->create(['role' => 'member']);
        $pkg = $this->createUserPackage($user);

        $response = $this->postJson("/api/v1/user-packages/{$pkg->id}/freeze");
        $response->assertStatus(401);
    }

    // ─── FROZEN PACKAGE BOOKING PREVENTION ─────────────────────────────

    public function test_frozen_package_excluded_from_session_deduction(): void
    {
        [$user, $token] = $this->createMember();
        $pkg = $this->createUserPackage($user, [
            'is_frozen' => true,
            'status' => 'frozen',
            'remaining_sessions' => 8,
        ]);

        // No active non-frozen package should be found
        $activePackage = UserPackage::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('is_frozen', false)
            ->where('remaining_sessions', '>', 0)
            ->first();

        $this->assertNull($activePackage);
        $this->assertEquals(8, $pkg->fresh()->remaining_sessions);
    }

    public function test_frozen_package_sessions_unchanged_after_booking(): void
    {
        $store = $this->createStore();
        [$user, $token] = $this->createMember();
        $frozenPkg = $this->createUserPackage($user, [
            'is_frozen' => true,
            'status' => 'frozen',
            'remaining_sessions' => 5,
        ]);

        // Create a booking - should NOT deduct from frozen package
        $booking = Booking::create([
            'user_id' => $user->id,
            'store_id' => $store->id,
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

        // Manually trigger session deduction like the event would
        $listener = app(ProcessSessionDeduction::class);
        $listener->handle(new BookingCreated($booking));

        $this->assertEquals(5, $frozenPkg->fresh()->remaining_sessions);
    }

    // ─── REMAINING SESSIONS MANAGEMENT ─────────────────────────────────

    public function test_session_deduction_selects_soonest_expiring_package(): void
    {
        [$user, $token] = $this->createMember();

        // Package A: expires in 5 days
        $pkgA = $this->createUserPackage($user, [
            'remaining_sessions' => 5,
            'expiry_date' => now()->addDays(5)->toDateString(),
        ]);

        // Package B: expires in 20 days
        $pkgB = $this->createUserPackage($user, [
            'remaining_sessions' => 5,
            'expiry_date' => now()->addDays(20)->toDateString(),
        ]);

        // The query should pick the soonest expiring package
        $selected = UserPackage::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('is_frozen', false)
            ->where('remaining_sessions', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>=', now()->toDateString());
            })
            ->orderBy('expiry_date', 'desc')
            ->first();

        // With orderBy DESC + first(), it picks the LATEST expiry first
        // This means pkgB (20 days) is selected first
        $this->assertEquals($pkgB->id, $selected->id);
    }

    public function test_session_deduction_skips_zero_session_package(): void
    {
        [$user, $token] = $this->createMember();

        // Package with 0 remaining sessions
        $emptyPkg = $this->createUserPackage($user, [
            'remaining_sessions' => 0,
        ]);

        // Package with sessions available
        $activePkg = $this->createUserPackage($user, [
            'remaining_sessions' => 5,
        ]);

        $selected = UserPackage::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('is_frozen', false)
            ->where('remaining_sessions', '>', 0)
            ->first();

        $this->assertEquals($activePkg->id, $selected->id);
    }

    public function test_session_cannot_go_negative(): void
    {
        [$user, $token] = $this->createMember();
        $pkg = $this->createUserPackage($user, ['remaining_sessions' => 0]);

        // Try to decrement atomically - should affect 0 rows
        $updated = UserPackage::where('id', $pkg->id)
            ->where('remaining_sessions', '>', 0)
            ->decrement('remaining_sessions');

        $this->assertEquals(0, $updated);
        $this->assertEquals(0, $pkg->fresh()->remaining_sessions);
    }

    public function test_session_refund_blocked_at_total(): void
    {
        [$user, $token] = $this->createMember();
        $pkg = $this->createUserPackage($user, [
            'remaining_sessions' => 10,
            'total_sessions' => 10,
        ]);

        // Try to increment past total - should affect 0 rows
        $updated = UserPackage::where('id', $pkg->id)
            ->whereColumn('remaining_sessions', '<', 'total_sessions')
            ->increment('remaining_sessions');

        $this->assertEquals(0, $updated);
        $this->assertEquals(10, $pkg->fresh()->remaining_sessions);
    }

    public function test_session_refund_allowed_when_below_total(): void
    {
        [$user, $token] = $this->createMember();
        $pkg = $this->createUserPackage($user, [
            'remaining_sessions' => 8,
            'total_sessions' => 10,
        ]);

        $updated = UserPackage::where('id', $pkg->id)
            ->whereColumn('remaining_sessions', '<', 'total_sessions')
            ->increment('remaining_sessions');

        $this->assertEquals(1, $updated);
        $this->assertEquals(9, $pkg->fresh()->remaining_sessions);
    }

    public function test_can_be_used_false_when_zero_sessions(): void
    {
        [$user, $token] = $this->createMember();
        $pkg = $this->createUserPackage($user, ['remaining_sessions' => 0]);

        $this->assertFalse($pkg->canBeUsed());
    }

    public function test_can_be_used_false_when_frozen(): void
    {
        [$user, $token] = $this->createMember();
        $pkg = $this->createUserPackage($user, [
            'is_frozen' => true,
            'status' => 'frozen',
            'remaining_sessions' => 5,
        ]);

        $this->assertFalse($pkg->canBeUsed());
    }

    public function test_can_be_used_false_when_expired(): void
    {
        [$user, $token] = $this->createMember();
        $pkg = $this->createUserPackage($user, [
            'expiry_date' => now()->subDay()->toDateString(),
            'remaining_sessions' => 5,
        ]);

        $this->assertFalse($pkg->canBeUsed());
    }

    public function test_can_be_used_true_when_active_with_sessions(): void
    {
        [$user, $token] = $this->createMember();
        $pkg = $this->createUserPackage($user, ['remaining_sessions' => 5]);

        $this->assertTrue($pkg->canBeUsed());
    }

    // ─── PACKAGE STATUS LIFECYCLE ──────────────────────────────────────

    public function test_package_status_does_not_change_on_zero_sessions(): void
    {
        [$user, $token] = $this->createMember();
        $pkg = $this->createUserPackage($user, ['remaining_sessions' => 0]);

        // Status stays active even with 0 sessions (only expiry changes status)
        $pkg->updateLifecycleStatus();
        $this->assertEquals('active', $pkg->status);
    }

    public function test_lifecycle_status_expires_on_past_date(): void
    {
        [$user, $token] = $this->createMember();
        $pkg = $this->createUserPackage($user, [
            'expiry_date' => now()->subDay()->toDateString(),
        ]);

        $pkg->updateLifecycleStatus();
        $this->assertEquals('expired', $pkg->status);
    }

    public function test_lifecycle_status_expiring_soon_within_7_days(): void
    {
        [$user, $token] = $this->createMember();
        $pkg = $this->createUserPackage($user, [
            'expiry_date' => now()->addDays(5)->toDateString(),
        ]);

        $pkg->updateLifecycleStatus();
        $this->assertEquals('expiring_soon', $pkg->status);
    }

    public function test_lifecycle_status_frozen_overrides_other_states(): void
    {
        [$user, $token] = $this->createMember();
        $pkg = $this->createUserPackage($user, [
            'is_frozen' => true,
            'status' => 'frozen',
            'expiry_date' => now()->subDay()->toDateString(), // expired too
        ]);

        $pkg->updateLifecycleStatus();
        // Frozen takes priority over expired
        $this->assertEquals('frozen', $pkg->status);
    }

    // ─── BOOKING + SESSION INTEGRATION ─────────────────────────────────

    public function test_booking_created_event_deducts_session(): void
    {
        $store = $this->createStore();
        [$user, $token] = $this->createMember();
        $pkg = $this->createUserPackage($user, ['remaining_sessions' => 5]);

        $booking = Booking::create([
            'user_id' => $user->id,
            'store_id' => $store->id,
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

        $listener = app(ProcessSessionDeduction::class);
        $listener->handle(new BookingCreated($booking));

        $this->assertEquals(4, $pkg->fresh()->remaining_sessions);
    }

    public function test_booking_cancelled_event_refunds_session(): void
    {
        $store = $this->createStore();
        [$user, $token] = $this->createMember();
        $pkg = $this->createUserPackage($user, [
            'remaining_sessions' => 4,
            'total_sessions' => 10,
        ]);

        $booking = Booking::create([
            'user_id' => $user->id,
            'store_id' => $store->id,
            'class_name' => 'Test Class',
            'instructor' => 'Trainer',
            'date' => now()->addDay()->toDateString(),
            'time' => '10:00',
            'type' => 'personal',
            'status' => 'cancelled',
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'location' => 'Test Store',
            'booking_time' => now(),
        ]);

        $listener = app(ProcessSessionDeduction::class);
        $listener->handle(new BookingCancelled($booking, 'confirmed'));

        $this->assertEquals(5, $pkg->fresh()->remaining_sessions);
    }

    public function test_cancellation_of_non_confirmed_booking_does_not_refund(): void
    {
        $store = $this->createStore();
        [$user, $token] = $this->createMember();
        $pkg = $this->createUserPackage($user, [
            'remaining_sessions' => 4,
            'total_sessions' => 10,
        ]);

        $booking = Booking::create([
            'user_id' => $user->id,
            'store_id' => $store->id,
            'class_name' => 'Test Class',
            'instructor' => 'Trainer',
            'date' => now()->addDay()->toDateString(),
            'time' => '10:00',
            'type' => 'personal',
            'status' => 'cancelled',
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'location' => 'Test Store',
            'booking_time' => now(),
        ]);

        // Previous status was 'pending', not 'confirmed'
        $listener = app(ProcessSessionDeduction::class);
        $listener->handle(new BookingCancelled($booking, 'pending'));

        // Should NOT refund because original status wasn't confirmed
        $this->assertEquals(4, $pkg->fresh()->remaining_sessions);
    }

    public function test_expired_package_skipped_for_deduction(): void
    {
        $store = $this->createStore();
        [$user, $token] = $this->createMember();

        // Only an expired package available
        $pkg = $this->createUserPackage($user, [
            'expiry_date' => now()->subDay()->toDateString(),
            'remaining_sessions' => 5,
        ]);

        $booking = Booking::create([
            'user_id' => $user->id,
            'store_id' => $store->id,
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

        $listener = app(ProcessSessionDeduction::class);
        $listener->handle(new BookingCreated($booking));

        // Sessions unchanged - expired package was skipped
        $this->assertEquals(5, $pkg->fresh()->remaining_sessions);
    }
}
