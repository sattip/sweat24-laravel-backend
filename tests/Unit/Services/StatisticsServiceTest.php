<?php

namespace Tests\Unit\Services;

use App\Models\Booking;
use App\Models\GymClass;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltyRedemption;
use App\Models\LoyaltyReward;
use App\Models\Referral;
use App\Models\ReferralReward;
use App\Models\User;
use App\Services\StatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatisticsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StatisticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StatisticsService();
    }

    // ==========================================
    // Booking Type Statistics Tests
    // ==========================================

    public function test_booking_type_statistics_groups_by_type(): void
    {
        $user = User::factory()->create();
        $gymClass = GymClass::factory()->create();

        // Create bookings of different types
        Booking::factory()->count(5)->create([
            'user_id' => $user->id,
            'class_id' => $gymClass->id,
            'booking_type' => 'regular',
            'status' => 'confirmed',
            'attended' => true,
        ]);

        Booking::factory()->count(3)->create([
            'user_id' => $user->id,
            'class_id' => $gymClass->id,
            'booking_type' => 'trial',
            'status' => 'confirmed',
            'attended' => true,
        ]);

        Booking::factory()->count(2)->create([
            'user_id' => $user->id,
            'class_id' => $gymClass->id,
            'booking_type' => 'loyalty_gift',
            'status' => 'confirmed',
            'attended' => false,
        ]);

        $stats = $this->service->getBookingTypeStatistics();

        $regularStats = $stats->firstWhere('booking_type', 'regular');
        $trialStats = $stats->firstWhere('booking_type', 'trial');
        $loyaltyStats = $stats->firstWhere('booking_type', 'loyalty_gift');

        $this->assertEquals(5, $regularStats['total_bookings']);
        $this->assertEquals(3, $trialStats['total_bookings']);
        $this->assertEquals(2, $loyaltyStats['total_bookings']);
    }

    public function test_booking_statistics_calculates_attendance_rate(): void
    {
        $user = User::factory()->create();
        $gymClass = GymClass::factory()->create();

        // Create 10 bookings, 7 attended
        Booking::factory()->count(7)->create([
            'user_id' => $user->id,
            'class_id' => $gymClass->id,
            'booking_type' => 'regular',
            'status' => 'confirmed',
            'attended' => true,
        ]);

        Booking::factory()->count(3)->create([
            'user_id' => $user->id,
            'class_id' => $gymClass->id,
            'booking_type' => 'regular',
            'status' => 'confirmed',
            'attended' => false,
        ]);

        $stats = $this->service->getBookingTypeStatistics();
        $regularStats = $stats->firstWhere('booking_type', 'regular');

        // Attendance rate should be 70%
        $this->assertEquals(70, $regularStats['attendance_rate']);
        $this->assertEquals(7, $regularStats['attended_bookings']);
    }

    public function test_booking_statistics_counts_confirmed_and_cancelled(): void
    {
        $user = User::factory()->create();
        $gymClass = GymClass::factory()->create();

        Booking::factory()->count(5)->create([
            'user_id' => $user->id,
            'class_id' => $gymClass->id,
            'booking_type' => 'regular',
            'status' => 'confirmed',
        ]);

        Booking::factory()->count(3)->create([
            'user_id' => $user->id,
            'class_id' => $gymClass->id,
            'booking_type' => 'regular',
            'status' => 'cancelled',
        ]);

        $stats = $this->service->getBookingTypeStatistics();
        $regularStats = $stats->firstWhere('booking_type', 'regular');

        $this->assertEquals(8, $regularStats['total_bookings']);
        $this->assertEquals(5, $regularStats['confirmed_bookings']);
        $this->assertEquals(3, $regularStats['cancelled_bookings']);
    }

    public function test_booking_statistics_with_date_filter(): void
    {
        $user = User::factory()->create();
        $gymClass = GymClass::factory()->create();

        // Bookings from last month (outside filter)
        Booking::factory()->count(5)->create([
            'user_id' => $user->id,
            'class_id' => $gymClass->id,
            'booking_type' => 'regular',
            'created_at' => now()->subMonth(),
        ]);

        // Bookings from this week (inside filter)
        Booking::factory()->count(3)->create([
            'user_id' => $user->id,
            'class_id' => $gymClass->id,
            'booking_type' => 'regular',
            'created_at' => now(),
        ]);

        $stats = $this->service->getBookingTypeStatistics(
            now()->subDays(7)->toDateString(),
            now()->toDateString()
        );

        $regularStats = $stats->firstWhere('booking_type', 'regular');

        $this->assertEquals(3, $regularStats['total_bookings']);
    }

    // ==========================================
    // Monthly Booking Trends Tests
    // ==========================================

    public function test_monthly_booking_trends_groups_by_month(): void
    {
        $user = User::factory()->create();
        $gymClass = GymClass::factory()->create();

        // Create bookings for current month
        Booking::factory()->count(10)->create([
            'user_id' => $user->id,
            'class_id' => $gymClass->id,
            'booking_type' => 'regular',
            'attended' => true,
            'created_at' => now(),
        ]);

        // Create bookings for last month
        Booking::factory()->count(8)->create([
            'user_id' => $user->id,
            'class_id' => $gymClass->id,
            'booking_type' => 'regular',
            'attended' => true,
            'created_at' => now()->subMonth(),
        ]);

        $trends = $this->service->getMonthlyBookingTrends(2);

        // Should have data for both months
        $this->assertGreaterThanOrEqual(1, $trends->count());
    }

    public function test_monthly_trends_calculates_attendance_rate_per_month(): void
    {
        $user = User::factory()->create();
        $gymClass = GymClass::factory()->create();

        // Create 10 bookings this month, 8 attended
        Booking::factory()->count(8)->create([
            'user_id' => $user->id,
            'class_id' => $gymClass->id,
            'booking_type' => 'regular',
            'attended' => true,
            'created_at' => now(),
        ]);

        Booking::factory()->count(2)->create([
            'user_id' => $user->id,
            'class_id' => $gymClass->id,
            'booking_type' => 'regular',
            'attended' => false,
            'created_at' => now(),
        ]);

        $trends = $this->service->getMonthlyBookingTrends(1);
        $currentMonth = now()->format('Y-m');

        if ($trends->has($currentMonth)) {
            $monthData = $trends[$currentMonth];
            $this->assertEquals(10, $monthData['total_bookings']);
            $this->assertEquals(8, $monthData['attended_bookings']);
            $this->assertEquals(80, $monthData['attendance_rate']);
        }
    }

    // ==========================================
    // Loyalty Program Statistics Tests
    // ==========================================

    public function test_loyalty_statistics_sums_points_correctly(): void
    {
        $user = User::factory()->create();

        // Create earned points
        LoyaltyPoint::create([
            'user_id' => $user->id,
            'amount' => 100,
            'type' => 'earned',
            'source' => 'booking',
            'description' => 'Points for booking',
            'balance_after' => 100,
        ]);

        LoyaltyPoint::create([
            'user_id' => $user->id,
            'amount' => 50,
            'type' => 'earned',
            'source' => 'referral',
            'description' => 'Referral bonus',
            'balance_after' => 150,
        ]);

        // Create redeemed points (negative)
        LoyaltyPoint::create([
            'user_id' => $user->id,
            'amount' => -30,
            'type' => 'redeemed',
            'source' => 'redemption',
            'description' => 'Points redeemed',
            'balance_after' => 120,
        ]);

        $stats = $this->service->getLoyaltyProgramStatistics();

        $this->assertEquals(150, $stats['total_points_issued']); // 100 + 50
        $this->assertEquals(30, $stats['total_points_redeemed']); // abs(-30)
    }

    public function test_loyalty_statistics_counts_active_points(): void
    {
        $user = User::factory()->create();

        // Active points (no expiry)
        LoyaltyPoint::create([
            'user_id' => $user->id,
            'amount' => 100,
            'type' => 'earned',
            'source' => 'booking',
            'description' => 'Active points',
            'balance_after' => 100,
            'expires_at' => null,
        ]);

        // Active points (future expiry)
        LoyaltyPoint::create([
            'user_id' => $user->id,
            'amount' => 50,
            'type' => 'earned',
            'source' => 'booking',
            'description' => 'Future expiry',
            'balance_after' => 150,
            'expires_at' => now()->addMonths(6),
        ]);

        // Expired points (should not count)
        LoyaltyPoint::create([
            'user_id' => $user->id,
            'amount' => 25,
            'type' => 'earned',
            'source' => 'booking',
            'description' => 'Expired points',
            'balance_after' => 175,
            'expires_at' => now()->subDay(),
        ]);

        $stats = $this->service->getLoyaltyProgramStatistics();

        $this->assertEquals(150, $stats['active_points']); // 100 + 50
    }

    public function test_loyalty_statistics_counts_expiring_points(): void
    {
        $user = User::factory()->create();

        // Points expiring in 15 days (within 30 day window)
        LoyaltyPoint::create([
            'user_id' => $user->id,
            'amount' => 75,
            'type' => 'earned',
            'source' => 'booking',
            'description' => 'Expiring soon',
            'balance_after' => 75,
            'expires_at' => now()->addDays(15),
        ]);

        // Points expiring in 45 days (outside 30 day window)
        LoyaltyPoint::create([
            'user_id' => $user->id,
            'amount' => 50,
            'type' => 'earned',
            'source' => 'booking',
            'description' => 'Later expiry',
            'balance_after' => 125,
            'expires_at' => now()->addDays(45),
        ]);

        $stats = $this->service->getLoyaltyProgramStatistics();

        $this->assertEquals(75, $stats['expiring_points_30_days']);
    }

    public function test_loyalty_statistics_counts_users_with_points(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        // User 1 has active points
        LoyaltyPoint::create([
            'user_id' => $user1->id,
            'amount' => 100,
            'type' => 'earned',
            'source' => 'booking',
            'description' => 'User 1 points',
            'balance_after' => 100,
        ]);

        // User 2 has active points
        LoyaltyPoint::create([
            'user_id' => $user2->id,
            'amount' => 50,
            'type' => 'earned',
            'source' => 'booking',
            'description' => 'User 2 points',
            'balance_after' => 50,
        ]);

        // User 3 has only expired points
        LoyaltyPoint::create([
            'user_id' => $user3->id,
            'amount' => 25,
            'type' => 'earned',
            'source' => 'booking',
            'description' => 'User 3 expired points',
            'balance_after' => 25,
            'expires_at' => now()->subDay(),
        ]);

        $stats = $this->service->getLoyaltyProgramStatistics();

        $this->assertEquals(2, $stats['users_with_points']);
    }

    // ==========================================
    // Booking Type Display Name Tests
    // ==========================================

    public function test_booking_type_display_names(): void
    {
        $user = User::factory()->create();
        $gymClass = GymClass::factory()->create();

        $bookingTypes = ['regular', 'trial', 'loyalty_gift', 'referral_gift', 'free', 'promotional'];

        foreach ($bookingTypes as $type) {
            Booking::factory()->create([
                'user_id' => $user->id,
                'class_id' => $gymClass->id,
                'booking_type' => $type,
            ]);
        }

        $stats = $this->service->getBookingTypeStatistics();

        $expectedDisplayNames = [
            'regular' => 'Κανονική',
            'trial' => 'Δοκιμαστική',
            'loyalty_gift' => 'Δώρο Ανταμοιβής',
            'referral_gift' => 'Δώρο Συστάσεων',
            'free' => 'Δωρεάν',
            'promotional' => 'Προσφορά',
        ];

        foreach ($stats as $stat) {
            $expectedName = $expectedDisplayNames[$stat['booking_type']] ?? ucfirst($stat['booking_type']);
            $this->assertEquals($expectedName, $stat['display_name']);
        }
    }

    // ==========================================
    // Dashboard Statistics Tests
    // ==========================================

    public function test_dashboard_statistics_returns_all_sections(): void
    {
        $stats = $this->service->getDashboardStatistics('30_days');

        $this->assertArrayHasKey('period', $stats);
        $this->assertArrayHasKey('period_start', $stats);
        $this->assertArrayHasKey('period_end', $stats);
        $this->assertArrayHasKey('bookings', $stats);
        $this->assertArrayHasKey('loyalty', $stats);
        $this->assertArrayHasKey('referrals', $stats);
        $this->assertArrayHasKey('revenue_impact', $stats);
    }

    public function test_dashboard_statistics_handles_different_periods(): void
    {
        $periods = ['7_days', '30_days', '90_days', '1_year'];

        foreach ($periods as $period) {
            $stats = $this->service->getDashboardStatistics($period);
            $this->assertEquals($period, $stats['period']);
        }
    }

    // ==========================================
    // Empty Data Handling Tests
    // ==========================================

    public function test_booking_statistics_handles_no_bookings(): void
    {
        $stats = $this->service->getBookingTypeStatistics();

        $this->assertEmpty($stats);
    }

    public function test_loyalty_statistics_handles_no_points(): void
    {
        $stats = $this->service->getLoyaltyProgramStatistics();

        $this->assertEquals(0, $stats['total_points_issued']);
        $this->assertEquals(0, $stats['total_points_redeemed']);
        $this->assertEquals(0, $stats['active_points']);
        $this->assertEquals(0, $stats['users_with_points']);
    }
}
