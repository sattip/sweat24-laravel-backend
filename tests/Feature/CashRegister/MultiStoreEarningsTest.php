<?php

namespace Tests\Feature\CashRegister;

use App\Models\Booking;
use App\Models\CashRegisterEntry;
use App\Models\GymClass;
use App\Models\Package;
use App\Models\Store;
use App\Models\User;
use App\Models\UserPackage;
use App\Services\BookingCompletionService;
use App\Services\CashRegisterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiStoreEarningsTest extends TestCase
{
    use RefreshDatabase;

    protected CashRegisterService $cashRegisterService;
    protected BookingCompletionService $bookingCompletionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cashRegisterService = app(CashRegisterService::class);
        $this->bookingCompletionService = app(BookingCompletionService::class);
    }

    // ==========================================
    // Multi-Store Earnings Split Tests
    // ==========================================

    public function test_earnings_split_between_two_stores_based_on_usage(): void
    {
        // Setup: User with package worth €100 for 10 sessions (€10 per session)
        $storeA = Store::factory()->create(['name' => 'Store A']);
        $storeB = Store::factory()->create(['name' => 'Store B']);
        $user = User::factory()->create(['membership_type' => 'premium']);
        $package = Package::factory()->create(['price' => 100, 'sessions' => 10]);
        
        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'total_sessions' => 10,
            'remaining_sessions' => 10,
            'status' => 'active',
        ]);

        // User does 6 workouts at Store A
        for ($i = 0; $i < 6; $i++) {
            $this->createAndCompleteBooking($user, $storeA, $userPackage);
        }

        // User does 4 workouts at Store B
        for ($i = 0; $i < 4; $i++) {
            $this->createAndCompleteBooking($user, $storeB, $userPackage);
        }

        // Verify Store A earnings: 6 sessions × €10 = €60
        $storeAEarnings = CashRegisterEntry::where('store_id', $storeA->id)
            ->where('category', 'package_usage')
            ->sum('amount');

        // Verify Store B earnings: 4 sessions × €10 = €40
        $storeBEarnings = CashRegisterEntry::where('store_id', $storeB->id)
            ->where('category', 'package_usage')
            ->sum('amount');

        $this->assertEquals(60, (float) $storeAEarnings);
        $this->assertEquals(40, (float) $storeBEarnings);
    }

    public function test_total_store_earnings_equals_package_price(): void
    {
        $storeA = Store::factory()->create();
        $storeB = Store::factory()->create();
        $user = User::factory()->create(['membership_type' => 'premium']);
        $package = Package::factory()->create(['price' => 100, 'sessions' => 10]);
        
        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'total_sessions' => 10,
            'remaining_sessions' => 10,
            'status' => 'active',
        ]);

        // Use all 10 sessions across both stores
        for ($i = 0; $i < 7; $i++) {
            $this->createAndCompleteBooking($user, $storeA, $userPackage);
        }
        for ($i = 0; $i < 3; $i++) {
            $this->createAndCompleteBooking($user, $storeB, $userPackage);
        }

        // Total earnings across all stores should equal package price
        $totalEarnings = CashRegisterEntry::where('category', 'package_usage')
            ->whereIn('store_id', [$storeA->id, $storeB->id])
            ->sum('amount');

        $this->assertEquals(100, round((float) $totalEarnings, 2));
    }

    public function test_uneven_session_price_within_acceptable_variance(): void
    {
        // Package with price that doesn't divide evenly: €100 for 7 sessions
        // Note: Due to rounding (€14.29 × 7 = €100.03), small variance expected
        $storeA = Store::factory()->create();
        $storeB = Store::factory()->create();
        $user = User::factory()->create(['membership_type' => 'premium']);
        $package = Package::factory()->create(['price' => 100, 'sessions' => 7]);
        
        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'total_sessions' => 7,
            'remaining_sessions' => 7,
            'status' => 'active',
        ]);

        // 4 sessions at Store A, 3 sessions at Store B
        for ($i = 0; $i < 4; $i++) {
            $this->createAndCompleteBooking($user, $storeA, $userPackage);
        }
        for ($i = 0; $i < 3; $i++) {
            $this->createAndCompleteBooking($user, $storeB, $userPackage);
        }

        $totalEarnings = CashRegisterEntry::where('category', 'package_usage')
            ->sum('amount');

        // Allow small variance (within €0.10) due to rounding
        $this->assertEqualsWithDelta(100, (float) $totalEarnings, 0.10);
    }

    public function test_single_store_gets_all_earnings_when_exclusive(): void
    {
        $storeA = Store::factory()->create();
        $storeB = Store::factory()->create();
        $user = User::factory()->create(['membership_type' => 'premium']);
        $package = Package::factory()->create(['price' => 150, 'sessions' => 15]);
        
        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'total_sessions' => 15,
            'remaining_sessions' => 15,
            'status' => 'active',
        ]);

        // All 15 sessions at Store A only
        for ($i = 0; $i < 15; $i++) {
            $this->createAndCompleteBooking($user, $storeA, $userPackage);
        }

        $storeAEarnings = CashRegisterEntry::where('store_id', $storeA->id)
            ->where('category', 'package_usage')
            ->sum('amount');

        $storeBEarnings = CashRegisterEntry::where('store_id', $storeB->id)
            ->where('category', 'package_usage')
            ->sum('amount');

        $this->assertEquals(150, round((float) $storeAEarnings, 2));
        $this->assertEquals(0, (float) $storeBEarnings);
    }

    public function test_store_summary_reflects_package_usage_income(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create(['membership_type' => 'premium']);
        $package = Package::factory()->create(['price' => 80, 'sessions' => 8]);
        
        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'total_sessions' => 8,
            'remaining_sessions' => 8,
            'status' => 'active',
        ]);

        // Complete 5 sessions
        for ($i = 0; $i < 5; $i++) {
            $this->createAndCompleteBooking($user, $store, $userPackage);
        }

        $summary = $this->cashRegisterService->getStoreSummary($store->id);

        // 5 sessions × €10 = €50
        $this->assertEquals(50, (float) $summary['income']);
    }

    public function test_multiple_users_earnings_attributed_to_correct_stores(): void
    {
        $storeA = Store::factory()->create();
        $storeB = Store::factory()->create();
        
        // User 1: Package €100/10 sessions
        $user1 = User::factory()->create(['membership_type' => 'premium']);
        $package1 = Package::factory()->create(['price' => 100, 'sessions' => 10]);
        $userPackage1 = UserPackage::factory()->create([
            'user_id' => $user1->id,
            'package_id' => $package1->id,
            'total_sessions' => 10,
            'remaining_sessions' => 10,
            'status' => 'active',
        ]);

        // User 2: Package €200/20 sessions
        $user2 = User::factory()->create(['membership_type' => 'premium']);
        $package2 = Package::factory()->create(['price' => 200, 'sessions' => 20]);
        $userPackage2 = UserPackage::factory()->create([
            'user_id' => $user2->id,
            'package_id' => $package2->id,
            'total_sessions' => 20,
            'remaining_sessions' => 20,
            'status' => 'active',
        ]);

        // User 1: 3 sessions at Store A (3 × €10 = €30)
        for ($i = 0; $i < 3; $i++) {
            $this->createAndCompleteBooking($user1, $storeA, $userPackage1);
        }

        // User 2: 5 sessions at Store A (5 × €10 = €50)
        for ($i = 0; $i < 5; $i++) {
            $this->createAndCompleteBooking($user2, $storeA, $userPackage2);
        }

        // User 2: 3 sessions at Store B (3 × €10 = €30)
        for ($i = 0; $i < 3; $i++) {
            $this->createAndCompleteBooking($user2, $storeB, $userPackage2);
        }

        $storeAEarnings = CashRegisterEntry::where('store_id', $storeA->id)
            ->where('category', 'package_usage')
            ->sum('amount');

        $storeBEarnings = CashRegisterEntry::where('store_id', $storeB->id)
            ->where('category', 'package_usage')
            ->sum('amount');

        // Store A: User1 €30 + User2 €50 = €80
        $this->assertEquals(80, (float) $storeAEarnings);
        // Store B: User2 €30
        $this->assertEquals(30, (float) $storeBEarnings);
    }

    public function test_partial_package_usage_shows_proportional_split(): void
    {
        $storeA = Store::factory()->create();
        $storeB = Store::factory()->create();
        $user = User::factory()->create(['membership_type' => 'premium']);
        $package = Package::factory()->create(['price' => 100, 'sessions' => 10]);
        
        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'total_sessions' => 10,
            'remaining_sessions' => 10,
            'status' => 'active',
        ]);

        // Only use 6 of 10 sessions: 4 at A, 2 at B
        for ($i = 0; $i < 4; $i++) {
            $this->createAndCompleteBooking($user, $storeA, $userPackage);
        }
        for ($i = 0; $i < 2; $i++) {
            $this->createAndCompleteBooking($user, $storeB, $userPackage);
        }

        $storeAEarnings = CashRegisterEntry::where('store_id', $storeA->id)
            ->where('category', 'package_usage')
            ->sum('amount');

        $storeBEarnings = CashRegisterEntry::where('store_id', $storeB->id)
            ->where('category', 'package_usage')
            ->sum('amount');

        // Store A: 4 × €10 = €40
        $this->assertEquals(40, (float) $storeAEarnings);
        // Store B: 2 × €10 = €20
        $this->assertEquals(20, (float) $storeBEarnings);
        // Total used: €60 (not €100 because only 6 of 10 sessions used)
        $this->assertEquals(60, (float) $storeAEarnings + (float) $storeBEarnings);
    }

    public function test_earnings_percentage_matches_usage_percentage(): void
    {
        $storeA = Store::factory()->create();
        $storeB = Store::factory()->create();
        $user = User::factory()->create(['membership_type' => 'premium']);
        $package = Package::factory()->create(['price' => 200, 'sessions' => 20]);
        
        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'total_sessions' => 20,
            'remaining_sessions' => 20,
            'status' => 'active',
        ]);

        // 75% at Store A (15 sessions), 25% at Store B (5 sessions)
        for ($i = 0; $i < 15; $i++) {
            $this->createAndCompleteBooking($user, $storeA, $userPackage);
        }
        for ($i = 0; $i < 5; $i++) {
            $this->createAndCompleteBooking($user, $storeB, $userPackage);
        }

        $storeAEarnings = (float) CashRegisterEntry::where('store_id', $storeA->id)
            ->where('category', 'package_usage')
            ->sum('amount');

        $storeBEarnings = (float) CashRegisterEntry::where('store_id', $storeB->id)
            ->where('category', 'package_usage')
            ->sum('amount');

        $total = $storeAEarnings + $storeBEarnings;

        // Store A should have 75% of earnings
        $storeAPercentage = ($storeAEarnings / $total) * 100;
        $this->assertEquals(75, round($storeAPercentage));

        // Store B should have 25% of earnings
        $storeBPercentage = ($storeBEarnings / $total) * 100;
        $this->assertEquals(25, round($storeBPercentage));
    }

    // ==========================================
    // Edge Cases for Rounding
    // ==========================================

    public function test_rounding_variance_is_minimal(): void
    {
        // Test various session counts that don't divide evenly
        $testCases = [
            ['price' => 100, 'sessions' => 3],  // 33.33 per session
            ['price' => 100, 'sessions' => 6],  // 16.67 per session
            ['price' => 100, 'sessions' => 9],  // 11.11 per session
            ['price' => 50, 'sessions' => 7],   // 7.14 per session
        ];

        foreach ($testCases as $case) {
            $store = Store::factory()->create();
            $user = User::factory()->create(['membership_type' => 'premium']);
            $package = Package::factory()->create($case);
            
            $userPackage = UserPackage::factory()->create([
                'user_id' => $user->id,
                'package_id' => $package->id,
                'total_sessions' => $case['sessions'],
                'remaining_sessions' => $case['sessions'],
                'status' => 'active',
            ]);

            for ($i = 0; $i < $case['sessions']; $i++) {
                $this->createAndCompleteBooking($user, $store, $userPackage);
            }

            $totalEarnings = CashRegisterEntry::where('store_id', $store->id)
                ->where('category', 'package_usage')
                ->sum('amount');

            // Variance should be within €0.10 of package price
            $variance = abs($case['price'] - (float) $totalEarnings);
            $this->assertLessThanOrEqual(0.10, $variance, 
                "Package €{$case['price']}/{$case['sessions']} sessions has variance of €{$variance}");
        }
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    protected function createAndCompleteBooking(User $user, Store $store, UserPackage $userPackage): Booking
    {
        $gymClass = GymClass::factory()->create();
        
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'store_id' => $store->id,
            'class_id' => $gymClass->id,
            'status' => 'confirmed',
        ]);

        // Complete the booking using the service
        $this->bookingCompletionService->completeBooking($booking->id, 1);

        return $booking->fresh();
    }
}
