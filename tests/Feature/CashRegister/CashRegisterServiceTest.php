<?php

namespace Tests\Feature\CashRegister;

use App\Models\Booking;
use App\Models\CashRegisterEntry;
use App\Models\Package;
use App\Models\Store;
use App\Models\User;
use App\Models\UserPackage;
use App\Services\CashRegisterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashRegisterServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CashRegisterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CashRegisterService();
    }

    // ==========================================
    // Per-Training Cost Calculation Tests
    // ==========================================

    public function test_calculates_per_training_cost_correctly(): void
    {
        $package = Package::factory()->create(['price' => 100, 'sessions' => 10]);
        $userPackage = UserPackage::factory()->create([
            'package_id' => $package->id,
            'total_sessions' => 10,
        ]);

        $perTrainingCost = $this->service->calculatePerTrainingCost($userPackage);

        $this->assertEquals(10.00, $perTrainingCost);
    }

    public function test_calculates_per_training_cost_with_decimal_result(): void
    {
        $package = Package::factory()->create(['price' => 100, 'sessions' => 8]);
        $userPackage = UserPackage::factory()->create([
            'package_id' => $package->id,
            'total_sessions' => 8,
        ]);

        $perTrainingCost = $this->service->calculatePerTrainingCost($userPackage);

        // 100 / 8 = 12.50
        $this->assertEquals(12.50, $perTrainingCost);
    }

    public function test_calculates_per_training_cost_rounds_to_two_decimals(): void
    {
        $package = Package::factory()->create(['price' => 100, 'sessions' => 7]);
        $userPackage = UserPackage::factory()->create([
            'package_id' => $package->id,
            'total_sessions' => 7,
        ]);

        $perTrainingCost = $this->service->calculatePerTrainingCost($userPackage);

        // 100 / 7 = 14.285714... should round to 14.29
        $this->assertEquals(14.29, $perTrainingCost);
    }

    public function test_returns_zero_for_zero_sessions(): void
    {
        $package = Package::factory()->create(['price' => 100, 'sessions' => 0]);
        $userPackage = UserPackage::factory()->create([
            'package_id' => $package->id,
            'total_sessions' => 0,
        ]);

        $perTrainingCost = $this->service->calculatePerTrainingCost($userPackage);

        $this->assertEquals(0, $perTrainingCost);
    }

    public function test_calculates_expensive_package_per_training_cost(): void
    {
        $package = Package::factory()->create(['price' => 599.99, 'sessions' => 24]);
        $userPackage = UserPackage::factory()->create([
            'package_id' => $package->id,
            'total_sessions' => 24,
        ]);

        $perTrainingCost = $this->service->calculatePerTrainingCost($userPackage);

        // 599.99 / 24 = 25.00 (rounded)
        $this->assertEquals(25.00, $perTrainingCost);
    }

    // ==========================================
    // Rounding Adjustment Tests (Last Session)
    // ==========================================

    public function test_handles_rounding_adjustment_on_last_session(): void
    {
        $package = Package::factory()->create(['price' => 100, 'sessions' => 7]);
        $userPackage = UserPackage::factory()->create([
            'package_id' => $package->id,
            'total_sessions' => 7,
            'remaining_sessions' => 1, // Last session
        ]);

        $standardAmount = 14.29; // 100/7 rounded
        $adjustedAmount = $this->service->handleRoundingAdjustment($userPackage, $standardAmount);

        // After 6 sessions at 14.29 = 85.74
        // Last session should be 100 - 85.74 = 14.26 to match total
        $this->assertEquals(14.26, $adjustedAmount);
    }

    public function test_no_adjustment_when_not_last_session(): void
    {
        $package = Package::factory()->create(['price' => 100, 'sessions' => 7]);
        $userPackage = UserPackage::factory()->create([
            'package_id' => $package->id,
            'total_sessions' => 7,
            'remaining_sessions' => 3, // Not last session
        ]);

        $standardAmount = 14.29;
        $adjustedAmount = $this->service->handleRoundingAdjustment($userPackage, $standardAmount);

        $this->assertEquals(14.29, $adjustedAmount);
    }

    public function test_total_sessions_sum_equals_package_price(): void
    {
        $packagePrice = 100;
        $totalSessions = 7;
        
        $package = Package::factory()->create(['price' => $packagePrice, 'sessions' => $totalSessions]);
        $userPackage = UserPackage::factory()->create([
            'package_id' => $package->id,
            'total_sessions' => $totalSessions,
            'remaining_sessions' => $totalSessions,
        ]);

        $standardAmount = $this->service->calculatePerTrainingCost($userPackage);
        $totalCharged = 0;

        // Simulate all sessions
        for ($i = $totalSessions; $i >= 1; $i--) {
            $userPackage->remaining_sessions = $i;
            $amount = $this->service->handleRoundingAdjustment($userPackage, $standardAmount);
            $totalCharged += $amount;
        }

        // Total charged should equal package price exactly
        $this->assertEquals($packagePrice, round($totalCharged, 2));
    }

    // ==========================================
    // Store Summary Tests
    // ==========================================

    public function test_calculates_store_summary_correctly(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create();

        // Create some income entries
        CashRegisterEntry::factory()->count(3)->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'type' => 'income',
            'amount' => 100,
        ]);

        // Create some expense entries
        CashRegisterEntry::factory()->count(2)->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'type' => 'withdrawal',
            'amount' => 50,
        ]);

        $summary = $this->service->getStoreSummary($store->id);

        $this->assertEquals(300, $summary['income']);  // 3 x 100
        $this->assertEquals(100, $summary['expenses']); // 2 x 50
        $this->assertEquals(200, $summary['net']);      // 300 - 100
        $this->assertEquals(5, $summary['entries_count']);
    }

    public function test_store_summary_with_date_filter(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create();

        // Create entry from yesterday
        CashRegisterEntry::factory()->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'type' => 'income',
            'amount' => 100,
            'created_at' => now()->subDay(),
        ]);

        // Create entry from today
        CashRegisterEntry::factory()->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'type' => 'income',
            'amount' => 200,
            'created_at' => now(),
        ]);

        $summary = $this->service->getStoreSummary($store->id, now()->toDateString(), now()->toDateString());

        $this->assertEquals(200, $summary['income']); // Only today's entry
        $this->assertEquals(1, $summary['entries_count']);
    }

    // ==========================================
    // Package Consumption Report Tests
    // ==========================================

    public function test_generates_package_consumption_report(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create(['price' => 200, 'sessions' => 10]);
        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'total_sessions' => 10,
            'remaining_sessions' => 6, // Used 4 sessions
        ]);

        $report = $this->service->getPackageConsumptionReport($userPackage->id);

        $this->assertEquals(10, $report['total_sessions']);
        $this->assertEquals(4, $report['used_sessions']);
        $this->assertEquals(6, $report['remaining_sessions']);
        $this->assertEquals(20.00, $report['per_training_cost']); // 200/10
        $this->assertEquals(80.00, $report['total_revenue']);     // 4 x 20
    }

    public function test_package_consumption_report_with_no_usage(): void
    {
        $package = Package::factory()->create(['price' => 150, 'sessions' => 15]);
        $userPackage = UserPackage::factory()->create([
            'package_id' => $package->id,
            'total_sessions' => 15,
            'remaining_sessions' => 15, // No usage
        ]);

        $report = $this->service->getPackageConsumptionReport($userPackage->id);

        $this->assertEquals(0, $report['used_sessions']);
        $this->assertEquals(0, $report['total_revenue']);
    }

    public function test_package_consumption_report_fully_used(): void
    {
        $package = Package::factory()->create(['price' => 100, 'sessions' => 10]);
        $userPackage = UserPackage::factory()->create([
            'package_id' => $package->id,
            'total_sessions' => 10,
            'remaining_sessions' => 0, // Fully used
        ]);

        $report = $this->service->getPackageConsumptionReport($userPackage->id);

        $this->assertEquals(10, $report['used_sessions']);
        $this->assertEquals(0, $report['remaining_sessions']);
        $this->assertEquals(100.00, $report['total_revenue']); // Full package price
    }
}
