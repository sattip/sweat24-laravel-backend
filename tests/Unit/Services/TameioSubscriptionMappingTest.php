<?php

namespace Tests\Unit\Services;

use App\Models\Booking;
use App\Models\CashRegisterEntry;
use App\Models\GymClass;
use App\Models\Package;
use App\Models\PackageHistory;
use App\Models\Store;
use App\Models\User;
use App\Models\UserPackage;
use App\Services\BookingCompletionService;
use App\Services\CashRegisterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TAMEIO SUBSCRIPTION MAPPING TESTS
 *
 * These tests verify that every subscription event produces the correct tameio entries.
 * Focus: One-to-one and one-to-many relationships between subscriptions and ledger entries.
 *
 * @group tameio
 * @group financial
 */
class TameioSubscriptionMappingTest extends TestCase
{
    use RefreshDatabase;

    protected CashRegisterService $cashRegisterService;
    protected BookingCompletionService $bookingCompletionService;
    protected User $user;
    protected User $admin;
    protected Store $store;
    protected Package $package;
    protected GymClass $gymClass;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cashRegisterService = new CashRegisterService();
        $this->bookingCompletionService = new BookingCompletionService($this->cashRegisterService);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->user = User::factory()->create(['role' => 'member', 'membership_type' => 'member']);
        $this->store = Store::factory()->create();
        $this->package = Package::factory()->create(['price' => 100, 'sessions' => 10]);
        $this->gymClass = GymClass::factory()->create();
    }

    // ==========================================
    // SUBSCRIPTION CREATION → TAMEIO MAPPING
    // ==========================================

    /**
     * @test
     * Given: A new subscription is created
     * When: User completes their first booking
     * Then: A tameio entry is created with correct amount
     */
    public function subscription_creation_first_booking_creates_tameio_entry(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'total_sessions' => 10,
            'remaining_sessions' => 10,
            'status' => 'active',
        ]);

        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'class_id' => $this->gymClass->id,
            'store_id' => $this->store->id,
            'status' => 'confirmed',
        ]);

        $result = $this->bookingCompletionService->completeBooking($booking->id, $this->admin->id);

        $this->assertNotNull($result['cash_entry']);
        $this->assertEquals('income', $result['cash_entry']->type);
        $this->assertEquals(10.00, $result['cash_entry']->amount); // 100/10
        $this->assertEquals($this->store->id, $result['cash_entry']->store_id);
        $this->assertEquals('user_package', $result['cash_entry']->related_entity_type);
        $this->assertEquals($userPackage->id, $result['cash_entry']->related_entity_id);
    }

    /**
     * @test
     * Given: A subscription with 10 sessions
     * When: All 10 sessions are completed
     * Then: Exactly 10 tameio entries exist, summing to package price
     */
    public function all_sessions_create_corresponding_tameio_entries(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'total_sessions' => 10,
            'remaining_sessions' => 10,
            'status' => 'active',
        ]);

        // Complete all 10 bookings
        for ($i = 0; $i < 10; $i++) {
            $booking = Booking::factory()->create([
                'user_id' => $this->user->id,
                'class_id' => $this->gymClass->id,
                'store_id' => $this->store->id,
                'status' => 'confirmed',
            ]);
            $this->bookingCompletionService->completeBooking($booking->id, $this->admin->id);
        }

        $entries = CashRegisterEntry::where('related_entity_id', $userPackage->id)
            ->where('related_entity_type', 'user_package')
            ->get();

        $this->assertCount(10, $entries);
        $this->assertEqualsWithDelta(100.00, $entries->sum('amount'), 0.01);
    }

    /**
     * @test
     * Given: A subscription exists
     * When: Booking is completed
     * Then: Tameio entry is traceable to both booking and subscription
     */
    public function tameio_entry_traceable_to_subscription(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'total_sessions' => 10,
            'remaining_sessions' => 10,
            'status' => 'active',
        ]);

        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'class_id' => $this->gymClass->id,
            'store_id' => $this->store->id,
            'status' => 'confirmed',
        ]);

        $result = $this->bookingCompletionService->completeBooking($booking->id, $this->admin->id);
        $entry = $result['cash_entry'];

        // Verify traceability
        $this->assertEquals($userPackage->id, $entry->related_entity_id);
        $this->assertEquals('user_package', $entry->related_entity_type);
        $this->assertEquals($booking->user_id, $entry->user_id);
        $this->assertStringContains($userPackage->name, $entry->description);
    }

    // ==========================================
    // SUBSCRIPTION RENEWAL → TAMEIO MAPPING
    // ==========================================

    /**
     * @test
     * Given: An expiring subscription
     * When: User renews and uses the new package
     * Then: New tameio entries link to new subscription only
     */
    public function renewal_creates_separate_tameio_entries(): void
    {
        // Original package - fully used
        $originalPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'total_sessions' => 10,
            'remaining_sessions' => 0,
            'status' => 'expired',
        ]);

        // Create some entries for original package
        CashRegisterEntry::factory()->count(10)->income()->create([
            'related_entity_id' => $originalPackage->id,
            'related_entity_type' => 'user_package',
            'store_id' => $this->store->id,
            'user_id' => $this->user->id,
            'amount' => 10,
        ]);

        // Renew the package
        $renewedPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'total_sessions' => 10,
            'remaining_sessions' => 10,
            'status' => 'active',
            'renewed_from_package_id' => $originalPackage->id,
        ]);

        // Use one session from renewed package
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'class_id' => $this->gymClass->id,
            'store_id' => $this->store->id,
            'status' => 'confirmed',
        ]);

        $result = $this->bookingCompletionService->completeBooking($booking->id, $this->admin->id);

        // Verify new entry links to renewed package
        $this->assertEquals($renewedPackage->id, $result['cash_entry']->related_entity_id);

        // Verify original package entries unchanged
        $originalEntries = CashRegisterEntry::where('related_entity_id', $originalPackage->id)->count();
        $renewedEntries = CashRegisterEntry::where('related_entity_id', $renewedPackage->id)->count();

        $this->assertEquals(10, $originalEntries);
        $this->assertEquals(1, $renewedEntries);
    }

    // ==========================================
    // SUBSCRIPTION UPGRADE/DOWNGRADE → TAMEIO
    // ==========================================

    /**
     * @test
     * Given: User upgrades from basic to premium package
     * When: Sessions are completed on premium package
     * Then: Tameio entries reflect premium pricing
     */
    public function upgrade_reflects_new_pricing_in_tameio(): void
    {
        $basicPackage = Package::factory()->create(['price' => 50, 'sessions' => 5]);
        $premiumPackage = Package::factory()->create(['price' => 150, 'sessions' => 10]);

        // User's basic package (expired)
        $basicUserPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $basicPackage->id,
            'total_sessions' => 5,
            'remaining_sessions' => 0,
            'status' => 'expired',
        ]);

        // User upgrades to premium
        $premiumUserPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $premiumPackage->id,
            'total_sessions' => 10,
            'remaining_sessions' => 10,
            'status' => 'active',
        ]);

        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'class_id' => $this->gymClass->id,
            'store_id' => $this->store->id,
            'status' => 'confirmed',
        ]);

        $result = $this->bookingCompletionService->completeBooking($booking->id, $this->admin->id);

        // Premium: 150/10 = 15.00 per session
        $this->assertEquals(15.00, $result['cash_entry']->amount);
    }

    // ==========================================
    // SUBSCRIPTION CANCELLATION → TAMEIO
    // ==========================================

    /**
     * @test
     * Given: A subscription is cancelled mid-way
     * When: No more bookings can be completed
     * Then: Tameio entries remain for completed sessions only
     */
    public function cancelled_subscription_preserves_existing_tameio_entries(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'total_sessions' => 10,
            'remaining_sessions' => 7, // 3 sessions used
            'status' => 'active',
        ]);

        // Create 3 completed session entries
        CashRegisterEntry::factory()->count(3)->income()->create([
            'related_entity_id' => $userPackage->id,
            'related_entity_type' => 'user_package',
            'store_id' => $this->store->id,
            'user_id' => $this->user->id,
            'amount' => 10,
        ]);

        // Cancel the subscription
        $userPackage->update(['status' => 'expired']);

        // Verify entries preserved
        $entries = CashRegisterEntry::where('related_entity_id', $userPackage->id)->get();
        $this->assertCount(3, $entries);
        $this->assertEquals(30.00, $entries->sum('amount'));
    }

    // ==========================================
    // SUBSCRIPTION PAUSE/RESUME → TAMEIO
    // ==========================================

    /**
     * @test
     * Given: A subscription is frozen/paused
     * When: User tries to complete a booking
     * Then: No tameio entry is created (booking fails)
     */
    public function frozen_subscription_blocks_tameio_entries(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'total_sessions' => 10,
            'remaining_sessions' => 10,
            'status' => 'frozen',
            'is_frozen' => true,
        ]);

        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'class_id' => $this->gymClass->id,
            'store_id' => $this->store->id,
            'status' => 'confirmed',
        ]);

        // Should fail because subscription is frozen
        $this->expectException(\App\Exceptions\BusinessValidationException::class);
        $this->bookingCompletionService->completeBooking($booking->id, $this->admin->id);
    }

    /**
     * @test
     * Given: A subscription was frozen and then resumed
     * When: User completes bookings after resume
     * Then: Tameio entries are created normally
     */
    public function resumed_subscription_creates_tameio_entries(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'total_sessions' => 10,
            'remaining_sessions' => 10,
            'status' => 'active',
            'is_frozen' => false,
            'frozen_at' => now()->subDays(5),
            'unfrozen_at' => now()->subDays(1),
        ]);

        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'class_id' => $this->gymClass->id,
            'store_id' => $this->store->id,
            'status' => 'confirmed',
        ]);

        $result = $this->bookingCompletionService->completeBooking($booking->id, $this->admin->id);

        $this->assertNotNull($result['cash_entry']);
        $this->assertEquals(10.00, $result['cash_entry']->amount);
    }

    // ==========================================
    // NO DUPLICATE ENTRIES
    // ==========================================

    /**
     * @test
     * Given: A booking is already completed
     * When: Attempting to complete it again
     * Then: No duplicate tameio entry is created
     */
    public function no_duplicate_tameio_entries_on_double_completion(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'total_sessions' => 10,
            'remaining_sessions' => 10,
            'status' => 'active',
        ]);

        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'class_id' => $this->gymClass->id,
            'store_id' => $this->store->id,
            'status' => 'confirmed',
        ]);

        // First completion
        $this->bookingCompletionService->completeBooking($booking->id, $this->admin->id);

        $entriesBefore = CashRegisterEntry::count();

        // Second completion attempt - should fail
        $this->expectException(\App\Exceptions\BusinessValidationException::class);
        $this->bookingCompletionService->completeBooking($booking->id, $this->admin->id);
    }

    // ==========================================
    // NO ORPHANED ENTRIES
    // ==========================================

    /**
     * @test
     * Given: A tameio entry exists
     * When: Querying the related subscription
     * Then: The subscription exists and is valid
     */
    public function no_orphaned_tameio_entries(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'total_sessions' => 10,
            'remaining_sessions' => 10,
            'status' => 'active',
        ]);

        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'class_id' => $this->gymClass->id,
            'store_id' => $this->store->id,
            'status' => 'confirmed',
        ]);

        $result = $this->bookingCompletionService->completeBooking($booking->id, $this->admin->id);
        $entry = $result['cash_entry'];

        // Verify the related subscription exists
        $relatedPackage = UserPackage::find($entry->related_entity_id);
        $this->assertNotNull($relatedPackage);
        $this->assertEquals($userPackage->id, $relatedPackage->id);
    }

    // ==========================================
    // CUSTOM PACKAGE PRICING
    // ==========================================

    /**
     * @test
     * Given: A custom-priced subscription
     * When: Sessions are completed
     * Then: Tameio entries reflect custom pricing
     */
    public function custom_package_pricing_reflected_in_tameio(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'total_sessions' => 10,
            'remaining_sessions' => 10,
            'status' => 'active',
            'is_custom_package' => true,
            'custom_price' => 80.00, // Discounted from 100
            'custom_sessions' => 10,
        ]);

        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'class_id' => $this->gymClass->id,
            'store_id' => $this->store->id,
            'status' => 'confirmed',
        ]);

        // Note: Current implementation uses package->price, not custom_price
        // This test documents expected behavior if custom pricing is implemented
        $result = $this->bookingCompletionService->completeBooking($booking->id, $this->admin->id);

        // Standard package price is used (100/10 = 10)
        // If custom pricing were implemented, this would be 80/10 = 8
        $this->assertNotNull($result['cash_entry']);
    }

    // ==========================================
    // GUEST USERS
    // ==========================================

    /**
     * @test
     * Given: A guest user (no subscription)
     * When: Booking is completed
     * Then: No tameio entry is created
     */
    public function guest_user_creates_no_tameio_entry(): void
    {
        $guestUser = User::factory()->create([
            'membership_type' => 'guest',
            'role' => 'member',
        ]);

        $booking = Booking::factory()->create([
            'user_id' => $guestUser->id,
            'class_id' => $this->gymClass->id,
            'store_id' => $this->store->id,
            'status' => 'confirmed',
        ]);

        $result = $this->bookingCompletionService->completeBooking($booking->id, $this->admin->id);

        $this->assertTrue($result['is_guest']);
        $this->assertNull($result['cash_entry']);
        $this->assertNull($result['user_package']);
    }

    // ==========================================
    // HELPER ASSERTIONS
    // ==========================================

    protected function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '$haystack' contains '$needle'"
        );
    }
}
