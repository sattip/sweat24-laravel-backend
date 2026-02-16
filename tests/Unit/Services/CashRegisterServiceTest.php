<?php

namespace Tests\Unit\Services;

use App\Models\Booking;
use App\Models\CashRegisterEntry;
use App\Models\GymClass;
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
        $user = User::factory()->create();
        $package = Package::factory()->create(['price' => 100]);

        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'total_sessions' => 10,
        ]);

        $perTrainingCost = $this->service->calculatePerTrainingCost($userPackage);

        // 100 / 10 = 10.00
        $this->assertEquals(10.00, $perTrainingCost);
    }

    public function test_per_training_cost_rounds_to_two_decimals(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create(['price' => 100]);

        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'total_sessions' => 7, // 100/7 = 14.285714...
        ]);

        $perTrainingCost = $this->service->calculatePerTrainingCost($userPackage);

        // Should round to 14.29
        $this->assertEquals(14.29, $perTrainingCost);
    }

    public function test_per_training_cost_with_zero_sessions_returns_zero(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create(['price' => 100]);

        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'total_sessions' => 0,
        ]);

        $perTrainingCost = $this->service->calculatePerTrainingCost($userPackage);

        $this->assertEquals(0, $perTrainingCost);
    }

    public function test_per_training_cost_with_expensive_package(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create(['price' => 500]);

        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'total_sessions' => 12,
        ]);

        $perTrainingCost = $this->service->calculatePerTrainingCost($userPackage);

        // 500 / 12 = 41.666... rounds to 41.67
        $this->assertEquals(41.67, $perTrainingCost);
    }

    // ==========================================
    // Rounding Adjustment Tests (Last Session)
    // ==========================================

    public function test_rounding_adjustment_on_last_session(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create(['price' => 100]);

        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'total_sessions' => 7,
            'remaining_sessions' => 1, // This is the last session
        ]);

        $standardAmount = 14.29; // 100/7 rounded
        $adjustedAmount = $this->service->handleRoundingAdjustment($userPackage, $standardAmount);

        // Previous 6 sessions: 6 × 14.29 = 85.74
        // Package price: 100
        // Adjustment needed: 100 - 85.74 = 14.26
        $this->assertEquals(14.26, $adjustedAmount);
    }

    public function test_no_adjustment_for_non_last_session(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create(['price' => 100]);

        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'total_sessions' => 7,
            'remaining_sessions' => 3, // Not the last session
        ]);

        $standardAmount = 14.29;
        $adjustedAmount = $this->service->handleRoundingAdjustment($userPackage, $standardAmount);

        // Should return same amount
        $this->assertEquals(14.29, $adjustedAmount);
    }

    public function test_total_charges_equal_package_price_after_adjustment(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create(['price' => 100]);

        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'total_sessions' => 7,
            'remaining_sessions' => 7,
        ]);

        $standardAmount = $this->service->calculatePerTrainingCost($userPackage);
        $totalCharged = 0;

        // Simulate charging for all sessions
        for ($i = 7; $i >= 1; $i--) {
            $userPackage->remaining_sessions = $i;
            $amount = $this->service->handleRoundingAdjustment($userPackage, $standardAmount);
            $totalCharged += $amount;
        }

        // Total should exactly equal package price (allowing for minor floating point issues)
        $this->assertEqualsWithDelta(100.00, $totalCharged, 0.01);
    }

    // ==========================================
    // Multi-Store Income Recording Tests
    // ==========================================

    public function test_records_income_to_correct_store(): void
    {
        $user = User::factory()->create();
        $store1 = Store::factory()->create(['name' => 'Store 1']);
        $store2 = Store::factory()->create(['name' => 'Store 2']);
        $package = Package::factory()->create(['price' => 100]);
        $gymClass = GymClass::factory()->create();

        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'total_sessions' => 10,
        ]);

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'class_id' => $gymClass->id,
            'store_id' => $store1->id,
        ]);

        $entry = $this->service->recordPackageIncome($booking, $userPackage);

        $this->assertEquals($store1->id, $entry->store_id);
        $this->assertEquals('income', $entry->type);
        $this->assertEquals(10.00, $entry->amount);
    }

    public function test_income_records_for_different_stores(): void
    {
        $user = User::factory()->create();
        $store1 = Store::factory()->create(['name' => 'Store 1']);
        $store2 = Store::factory()->create(['name' => 'Store 2']);
        $package = Package::factory()->create(['price' => 100]);
        $gymClass = GymClass::factory()->create();

        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'total_sessions' => 10,
        ]);

        // Book at store 1
        $booking1 = Booking::factory()->create([
            'user_id' => $user->id,
            'class_id' => $gymClass->id,
            'store_id' => $store1->id,
        ]);

        // Book at store 2
        $booking2 = Booking::factory()->create([
            'user_id' => $user->id,
            'class_id' => $gymClass->id,
            'store_id' => $store2->id,
        ]);

        $entry1 = $this->service->recordPackageIncome($booking1, $userPackage);
        $entry2 = $this->service->recordPackageIncome($booking2, $userPackage);

        $this->assertEquals($store1->id, $entry1->store_id);
        $this->assertEquals($store2->id, $entry2->store_id);
    }

    // ==========================================
    // Store Summary Calculation Tests
    // ==========================================

    public function test_store_summary_calculates_totals(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create();

        // Create income entries
        CashRegisterEntry::factory()->count(3)->income()->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'amount' => 100,
        ]);

        // Create expense entries
        CashRegisterEntry::factory()->count(2)->withdrawal()->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'amount' => 50,
        ]);

        $summary = $this->service->getStoreSummary($store->id);

        $this->assertEquals($store->id, $summary['store_id']);
        $this->assertEquals(300, $summary['income']); // 3 × 100
        $this->assertEquals(100, $summary['expenses']); // 2 × 50
        $this->assertEquals(200, $summary['net']); // 300 - 100
        $this->assertEquals(5, $summary['entries_count']);
    }

    public function test_store_summary_filters_by_date(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create();

        // Old entries (2 weeks ago)
        CashRegisterEntry::factory()->income()->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'amount' => 200,
            'created_at' => now()->subWeeks(2),
        ]);

        // Recent entries (today)
        CashRegisterEntry::factory()->income()->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'amount' => 100,
            'created_at' => now(),
        ]);

        $summary = $this->service->getStoreSummary(
            $store->id,
            now()->subDays(7)->toDateString(),
            now()->toDateString()
        );

        $this->assertEquals(100, $summary['income']); // Only recent entry
        $this->assertEquals(1, $summary['entries_count']);
    }

    public function test_store_summary_isolates_stores(): void
    {
        $store1 = Store::factory()->create();
        $store2 = Store::factory()->create();
        $user = User::factory()->create();

        // Store 1 income
        CashRegisterEntry::factory()->income()->create([
            'store_id' => $store1->id,
            'user_id' => $user->id,
            'amount' => 150,
        ]);

        // Store 2 income
        CashRegisterEntry::factory()->income()->create([
            'store_id' => $store2->id,
            'user_id' => $user->id,
            'amount' => 250,
        ]);

        $summary1 = $this->service->getStoreSummary($store1->id);
        $summary2 = $this->service->getStoreSummary($store2->id);

        $this->assertEquals(150, $summary1['income']);
        $this->assertEquals(250, $summary2['income']);
    }

    // ==========================================
    // Expense Recording Tests
    // ==========================================

    public function test_records_expense_correctly(): void
    {
        $store = Store::factory()->create();

        $this->actingAs(User::factory()->create());

        $entry = $this->service->recordExpense([
            'amount' => 75.50,
            'description' => 'Office supplies',
            'category' => 'supplies',
            'store_id' => $store->id,
            'payment_method' => 'card',
        ]);

        $this->assertEquals('withdrawal', $entry->type);
        $this->assertEquals(75.50, $entry->amount);
        $this->assertEquals('supplies', $entry->category);
        $this->assertEquals($store->id, $entry->store_id);
        $this->assertEquals('card', $entry->payment_method);
    }

    public function test_expense_defaults_to_cash_payment(): void
    {
        $store = Store::factory()->create();

        $this->actingAs(User::factory()->create());

        $entry = $this->service->recordExpense([
            'amount' => 50,
            'category' => 'utilities',
            'store_id' => $store->id,
        ]);

        $this->assertEquals('cash', $entry->payment_method);
    }

    // ==========================================
    // Package Consumption Report Tests
    // ==========================================

    public function test_package_consumption_report(): void
    {
        $user = User::factory()->create(['name' => 'Test User']);
        $package = Package::factory()->create(['price' => 120, 'name' => 'Gold Package']);

        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'name' => 'Gold Package',
            'total_sessions' => 12,
            'remaining_sessions' => 5,
        ]);

        $report = $this->service->getPackageConsumptionReport($userPackage->id);

        $this->assertEquals($userPackage->id, $report['user_package_id']);
        $this->assertEquals('Gold Package', $report['package_name']);
        $this->assertEquals('Test User', $report['user_name']);
        $this->assertEquals(12, $report['total_sessions']);
        $this->assertEquals(7, $report['used_sessions']); // 12 - 5
        $this->assertEquals(5, $report['remaining_sessions']);
        $this->assertEquals(10.00, $report['per_training_cost']); // 120/12
        $this->assertEquals(70.00, $report['total_revenue']); // 7 × 10
    }

    // ==========================================
    // Multi-Store Revenue Distribution Tests
    // ==========================================

    public function test_package_usage_distributed_across_stores(): void
    {
        $user = User::factory()->create();
        $store1 = Store::factory()->create(['name' => 'Downtown']);
        $store2 = Store::factory()->create(['name' => 'Uptown']);
        $package = Package::factory()->create(['price' => 100]);
        $gymClass = GymClass::factory()->create();

        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'total_sessions' => 10,
        ]);

        // 6 sessions at store 1
        for ($i = 0; $i < 6; $i++) {
            $booking = Booking::factory()->create([
                'user_id' => $user->id,
                'class_id' => $gymClass->id,
                'store_id' => $store1->id,
            ]);
            $this->service->recordPackageIncome($booking, $userPackage);
        }

        // 4 sessions at store 2
        for ($i = 0; $i < 4; $i++) {
            $booking = Booking::factory()->create([
                'user_id' => $user->id,
                'class_id' => $gymClass->id,
                'store_id' => $store2->id,
            ]);
            $this->service->recordPackageIncome($booking, $userPackage);
        }

        $store1Summary = $this->service->getStoreSummary($store1->id);
        $store2Summary = $this->service->getStoreSummary($store2->id);

        // Store 1: 6 sessions × 10 = 60
        $this->assertEquals(60, $store1Summary['income']);

        // Store 2: 4 sessions × 10 = 40
        $this->assertEquals(40, $store2Summary['income']);

        // Total should equal package price
        $this->assertEquals(100, $store1Summary['income'] + $store2Summary['income']);
    }

    public function test_combined_stores_total_equals_package_price(): void
    {
        $user = User::factory()->create();
        $stores = Store::factory()->count(3)->create();
        $package = Package::factory()->create(['price' => 150]);
        $gymClass = GymClass::factory()->create();

        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'total_sessions' => 15,
        ]);

        $perSession = $this->service->calculatePerTrainingCost($userPackage);
        $totalRecorded = 0;

        // Distribute sessions across stores
        foreach ($stores as $store) {
            for ($i = 0; $i < 5; $i++) {
                $booking = Booking::factory()->create([
                    'user_id' => $user->id,
                    'class_id' => $gymClass->id,
                    'store_id' => $store->id,
                ]);
                $entry = $this->service->recordPackageIncome($booking, $userPackage);
                $totalRecorded += $entry->amount;
            }
        }

        // Total recorded across all stores should equal package price
        // (allowing for small rounding differences)
        $this->assertEqualsWithDelta(150, $totalRecorded, 0.15);
    }

    // ==========================================
    // Edge Cases Tests
    // ==========================================

    public function test_handles_single_session_package(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create(['price' => 25]);

        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'total_sessions' => 1,
        ]);

        $perTrainingCost = $this->service->calculatePerTrainingCost($userPackage);

        $this->assertEquals(25.00, $perTrainingCost);
    }

    public function test_handles_large_session_count(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create(['price' => 1000]);

        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'total_sessions' => 100,
        ]);

        $perTrainingCost = $this->service->calculatePerTrainingCost($userPackage);

        $this->assertEquals(10.00, $perTrainingCost);
    }

    public function test_empty_store_summary(): void
    {
        $store = Store::factory()->create();

        $summary = $this->service->getStoreSummary($store->id);

        $this->assertEquals(0, $summary['income']);
        $this->assertEquals(0, $summary['expenses']);
        $this->assertEquals(0, $summary['net']);
        $this->assertEquals(0, $summary['entries_count']);
    }
}
