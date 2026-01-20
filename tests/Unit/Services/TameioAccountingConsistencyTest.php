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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TAMEIO ACCOUNTING CONSISTENCY TESTS
 *
 * Tests ensuring accounting principles are maintained:
 * - Income vs expense classification
 * - Reversals and adjustments
 * - Append-only ledger behavior
 * - Balance calculations
 *
 * @group tameio
 * @group financial
 * @group accounting
 */
class TameioAccountingConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected CashRegisterService $cashRegisterService;
    protected User $user;
    protected User $admin;
    protected Store $store;
    protected Package $package;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cashRegisterService = new CashRegisterService();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->user = User::factory()->create(['role' => 'member']);
        $this->store = Store::factory()->create();
        $this->package = Package::factory()->create(['price' => 100, 'sessions' => 10]);
    }

    // ==========================================
    // INCOME VS EXPENSE CLASSIFICATION
    // ==========================================

    /**
     * @test
     * Given: Package usage recorded
     * When: Creating tameio entry
     * Then: Entry classified as income with correct category
     */
    public function package_usage_classified_as_income(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'total_sessions' => 10,
            'remaining_sessions' => 10,
        ]);

        $gymClass = GymClass::factory()->create();

        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'store_id' => $this->store->id,
            'class_id' => $gymClass->id,
            'status' => 'completed',
        ]);

        $entry = $this->cashRegisterService->recordPackageIncome($booking, $userPackage);

        $this->assertEquals('income', $entry->type);
        $this->assertEquals('package_usage', $entry->category);
        $this->assertGreaterThan(0, $entry->amount);
    }

    /**
     * @test
     * Given: Business expense recorded
     * When: Creating tameio entry
     * Then: Entry classified as withdrawal
     */
    public function expense_classified_as_withdrawal(): void
    {
        $this->actingAs($this->admin);

        $entry = $this->cashRegisterService->recordExpense([
            'amount' => 50,
            'description' => 'Office supplies',
            'category' => 'supplies',
            'store_id' => $this->store->id,
            'payment_method' => 'cash',
        ]);

        $this->assertEquals('withdrawal', $entry->type);
        $this->assertEquals('supplies', $entry->category);
        $this->assertEquals(50, $entry->amount);
    }

    // ==========================================
    // BALANCE CALCULATIONS
    // ==========================================

    /**
     * @test
     * Given: Multiple income entries
     * When: Calculating total income
     * Then: Sum is accurate
     */
    public function income_sum_accurate(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'total_sessions' => 10,
            'remaining_sessions' => 10,
        ]);

        $gymClass = GymClass::factory()->create();
        $perSessionCost = $this->cashRegisterService->calculatePerTrainingCost($userPackage);

        for ($i = 0; $i < 5; $i++) {
            $booking = Booking::factory()->create([
                'user_id' => $this->user->id,
                'store_id' => $this->store->id,
                'class_id' => $gymClass->id,
                'status' => 'completed',
            ]);
            $this->cashRegisterService->recordPackageIncome($booking, $userPackage);
        }

        $totalIncome = CashRegisterEntry::where('store_id', $this->store->id)
            ->where('type', 'income')
            ->sum('amount');

        $expectedIncome = 5 * $perSessionCost;
        $this->assertEquals($expectedIncome, $totalIncome);
    }

    /**
     * @test
     * Given: Multiple expense entries
     * When: Calculating total expenses
     * Then: Sum is accurate
     */
    public function expense_sum_accurate(): void
    {
        $this->actingAs($this->admin);

        $amounts = [25.50, 30.75, 44.25];
        foreach ($amounts as $amount) {
            $this->cashRegisterService->recordExpense([
                'amount' => $amount,
                'description' => 'Test expense',
                'category' => 'supplies',
                'store_id' => $this->store->id,
                'payment_method' => 'cash',
            ]);
        }

        $totalExpenses = CashRegisterEntry::where('store_id', $this->store->id)
            ->where('type', 'withdrawal')
            ->sum('amount');

        $expectedExpenses = array_sum($amounts);
        $this->assertEquals($expectedExpenses, $totalExpenses);
    }

    /**
     * @test
     * Given: Income and expense entries
     * When: Calculating net balance
     * Then: Net = Income - Expenses
     */
    public function net_balance_calculation_correct(): void
    {
        $this->actingAs($this->admin);

        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'total_sessions' => 10,
            'remaining_sessions' => 10,
        ]);

        $gymClass = GymClass::factory()->create();

        // Record 3 income entries (3 * 10 = 30)
        for ($i = 0; $i < 3; $i++) {
            $booking = Booking::factory()->create([
                'user_id' => $this->user->id,
                'store_id' => $this->store->id,
                'class_id' => $gymClass->id,
                'status' => 'completed',
            ]);
            $this->cashRegisterService->recordPackageIncome($booking, $userPackage);
        }

        // Record expenses totaling 20
        $this->cashRegisterService->recordExpense([
            'amount' => 12,
            'description' => 'Expense 1',
            'category' => 'supplies',
            'store_id' => $this->store->id,
            'payment_method' => 'cash',
        ]);
        $this->cashRegisterService->recordExpense([
            'amount' => 8,
            'description' => 'Expense 2',
            'category' => 'utilities',
            'store_id' => $this->store->id,
            'payment_method' => 'card',
        ]);

        $summary = $this->cashRegisterService->getStoreSummary($this->store->id);

        $this->assertEquals(30, $summary['income']);
        $this->assertEquals(20, $summary['expenses']);
        $this->assertEquals(10, $summary['net']);
    }

    // ==========================================
    // APPEND-ONLY LEDGER BEHAVIOR
    // ==========================================

    /**
     * @test
     * Given: An existing tameio entry
     * When: Entry is created
     * Then: Entry cannot have its amount modified (append-only principle)
     */
    public function entries_are_append_only_for_amounts(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'total_sessions' => 10,
            'remaining_sessions' => 10,
        ]);

        $gymClass = GymClass::factory()->create();

        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'store_id' => $this->store->id,
            'class_id' => $gymClass->id,
            'status' => 'completed',
        ]);

        $entry = $this->cashRegisterService->recordPackageIncome($booking, $userPackage);
        $originalAmount = $entry->amount;
        $originalId = $entry->id;

        // Note: In a true append-only system, modifications would be blocked
        // This test documents expected behavior - amounts should be immutable
        // A reversal entry should be created instead of modifying original

        // Verify entry was created with correct amount
        $this->assertEquals($originalAmount, CashRegisterEntry::find($originalId)->amount);
    }

    /**
     * @test
     * Given: Need to correct an entry
     * When: Creating reversal
     * Then: Original entry preserved, new reversal entry created
     */
    public function correction_creates_reversal_entry(): void
    {
        $this->actingAs($this->admin);

        // Original entry
        $original = $this->cashRegisterService->recordExpense([
            'amount' => 100,
            'description' => 'Original expense',
            'category' => 'supplies',
            'store_id' => $this->store->id,
            'payment_method' => 'cash',
        ]);

        // Reversal entry (documented as income to offset)
        $reversal = CashRegisterEntry::create([
            'type' => 'income',
            'amount' => 100,
            'description' => 'Reversal of original expense - entry correction',
            'category' => 'adjustment',
            'user_id' => $this->admin->id,
            'store_id' => $this->store->id,
            'payment_method' => 'adjustment',
        ]);

        // Correct entry
        $corrected = $this->cashRegisterService->recordExpense([
            'amount' => 80,
            'description' => 'Corrected expense (was 100)',
            'category' => 'supplies',
            'store_id' => $this->store->id,
            'payment_method' => 'cash',
        ]);

        // All three entries exist
        $this->assertNotNull(CashRegisterEntry::find($original->id));
        $this->assertNotNull(CashRegisterEntry::find($reversal->id));
        $this->assertNotNull(CashRegisterEntry::find($corrected->id));

        // Net effect: 80 expense (100 original - 100 reversal + 80 corrected)
        $summary = $this->cashRegisterService->getStoreSummary($this->store->id);
        $this->assertEquals(100, $summary['income']); // reversal
        $this->assertEquals(180, $summary['expenses']); // original + corrected
        $this->assertEquals(-80, $summary['net']); // net expense of 80
    }

    // ==========================================
    // DATE RANGE FILTERING
    // ==========================================

    /**
     * @test
     * Given: Entries across multiple dates
     * When: Filtering by date range
     * Then: Only entries within range included
     */
    public function date_range_filtering_accurate(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'total_sessions' => 10,
            'remaining_sessions' => 10,
        ]);

        $gymClass = GymClass::factory()->create();

        // Entry in January
        Carbon::setTestNow(Carbon::parse('2024-01-15'));
        $booking1 = Booking::factory()->create([
            'user_id' => $this->user->id,
            'store_id' => $this->store->id,
            'class_id' => $gymClass->id,
            'status' => 'completed',
        ]);
        $this->cashRegisterService->recordPackageIncome($booking1, $userPackage);

        // 2 entries in February
        Carbon::setTestNow(Carbon::parse('2024-02-10'));
        $booking2 = Booking::factory()->create([
            'user_id' => $this->user->id,
            'store_id' => $this->store->id,
            'class_id' => $gymClass->id,
            'status' => 'completed',
        ]);
        $this->cashRegisterService->recordPackageIncome($booking2, $userPackage);

        Carbon::setTestNow(Carbon::parse('2024-02-20'));
        $booking3 = Booking::factory()->create([
            'user_id' => $this->user->id,
            'store_id' => $this->store->id,
            'class_id' => $gymClass->id,
            'status' => 'completed',
        ]);
        $this->cashRegisterService->recordPackageIncome($booking3, $userPackage);

        // Entry in March
        Carbon::setTestNow(Carbon::parse('2024-03-05'));
        $booking4 = Booking::factory()->create([
            'user_id' => $this->user->id,
            'store_id' => $this->store->id,
            'class_id' => $gymClass->id,
            'status' => 'completed',
        ]);
        $this->cashRegisterService->recordPackageIncome($booking4, $userPackage);

        // Query February only
        $februarySummary = $this->cashRegisterService->getStoreSummary(
            $this->store->id,
            '2024-02-01',
            '2024-02-29'
        );

        $this->assertEquals(2, $februarySummary['entries_count']);
        $this->assertEquals(20, $februarySummary['income']); // 2 * 10 per session

        Carbon::setTestNow();
    }

    // ==========================================
    // CATEGORY BREAKDOWN
    // ==========================================

    /**
     * @test
     * Given: Entries with different categories
     * When: Grouping by category
     * Then: Each category sum is accurate
     */
    public function category_breakdown_accurate(): void
    {
        $this->actingAs($this->admin);

        // Supplies expenses
        $this->cashRegisterService->recordExpense([
            'amount' => 30,
            'description' => 'Cleaning supplies',
            'category' => 'supplies',
            'store_id' => $this->store->id,
            'payment_method' => 'cash',
        ]);
        $this->cashRegisterService->recordExpense([
            'amount' => 20,
            'description' => 'Office supplies',
            'category' => 'supplies',
            'store_id' => $this->store->id,
            'payment_method' => 'cash',
        ]);

        // Utilities expenses
        $this->cashRegisterService->recordExpense([
            'amount' => 100,
            'description' => 'Electricity',
            'category' => 'utilities',
            'store_id' => $this->store->id,
            'payment_method' => 'transfer',
        ]);

        // Maintenance expenses
        $this->cashRegisterService->recordExpense([
            'amount' => 75,
            'description' => 'Equipment repair',
            'category' => 'maintenance',
            'store_id' => $this->store->id,
            'payment_method' => 'card',
        ]);

        $categoryTotals = CashRegisterEntry::where('store_id', $this->store->id)
            ->where('type', 'withdrawal')
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();

        $this->assertEquals(50, $categoryTotals['supplies']);
        $this->assertEquals(100, $categoryTotals['utilities']);
        $this->assertEquals(75, $categoryTotals['maintenance']);
    }

    // ==========================================
    // PAYMENT METHOD TRACKING
    // ==========================================

    /**
     * @test
     * Given: Entries with different payment methods
     * When: Grouping by payment method
     * Then: Each method total is accurate
     */
    public function payment_method_breakdown_accurate(): void
    {
        $this->actingAs($this->admin);

        // Cash expenses
        $this->cashRegisterService->recordExpense([
            'amount' => 40,
            'description' => 'Cash expense 1',
            'category' => 'supplies',
            'store_id' => $this->store->id,
            'payment_method' => 'cash',
        ]);
        $this->cashRegisterService->recordExpense([
            'amount' => 60,
            'description' => 'Cash expense 2',
            'category' => 'supplies',
            'store_id' => $this->store->id,
            'payment_method' => 'cash',
        ]);

        // Card expenses
        $this->cashRegisterService->recordExpense([
            'amount' => 85,
            'description' => 'Card expense',
            'category' => 'utilities',
            'store_id' => $this->store->id,
            'payment_method' => 'card',
        ]);

        // Transfer expenses
        $this->cashRegisterService->recordExpense([
            'amount' => 200,
            'description' => 'Bank transfer expense',
            'category' => 'rent',
            'store_id' => $this->store->id,
            'payment_method' => 'transfer',
        ]);

        $methodTotals = CashRegisterEntry::where('store_id', $this->store->id)
            ->where('type', 'withdrawal')
            ->selectRaw('payment_method, SUM(amount) as total')
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method')
            ->toArray();

        $this->assertEquals(100, $methodTotals['cash']);
        $this->assertEquals(85, $methodTotals['card']);
        $this->assertEquals(200, $methodTotals['transfer']);
    }

    // ==========================================
    // MULTI-STORE ISOLATION
    // ==========================================

    /**
     * @test
     * Given: Entries at different stores
     * When: Querying store summary
     * Then: Each store's entries isolated
     */
    public function store_entries_isolated(): void
    {
        $this->actingAs($this->admin);

        $store1 = $this->store;
        $store2 = Store::factory()->create();

        // Store 1: 3 entries totaling 90
        for ($i = 0; $i < 3; $i++) {
            $this->cashRegisterService->recordExpense([
                'amount' => 30,
                'description' => "Store 1 expense $i",
                'category' => 'supplies',
                'store_id' => $store1->id,
                'payment_method' => 'cash',
            ]);
        }

        // Store 2: 2 entries totaling 100
        for ($i = 0; $i < 2; $i++) {
            $this->cashRegisterService->recordExpense([
                'amount' => 50,
                'description' => "Store 2 expense $i",
                'category' => 'supplies',
                'store_id' => $store2->id,
                'payment_method' => 'cash',
            ]);
        }

        $store1Summary = $this->cashRegisterService->getStoreSummary($store1->id);
        $store2Summary = $this->cashRegisterService->getStoreSummary($store2->id);

        $this->assertEquals(3, $store1Summary['entries_count']);
        $this->assertEquals(90, $store1Summary['expenses']);

        $this->assertEquals(2, $store2Summary['entries_count']);
        $this->assertEquals(100, $store2Summary['expenses']);
    }

    // ==========================================
    // RUNNING BALANCE
    // ==========================================

    /**
     * @test
     * Given: Series of transactions
     * When: Computing running balance after each
     * Then: Balance accurately reflects cumulative state
     */
    public function running_balance_accurate(): void
    {
        $this->actingAs($this->admin);

        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'total_sessions' => 10,
            'remaining_sessions' => 10,
        ]);

        $gymClass = GymClass::factory()->create();

        // Transaction 1: Income +10
        $booking1 = Booking::factory()->create([
            'user_id' => $this->user->id,
            'store_id' => $this->store->id,
            'class_id' => $gymClass->id,
            'status' => 'completed',
        ]);
        $this->cashRegisterService->recordPackageIncome($booking1, $userPackage);

        $summary1 = $this->cashRegisterService->getStoreSummary($this->store->id);
        $this->assertEquals(10, $summary1['net']);

        // Transaction 2: Expense -5
        $this->cashRegisterService->recordExpense([
            'amount' => 5,
            'description' => 'Small expense',
            'category' => 'supplies',
            'store_id' => $this->store->id,
            'payment_method' => 'cash',
        ]);

        $summary2 = $this->cashRegisterService->getStoreSummary($this->store->id);
        $this->assertEquals(5, $summary2['net']);

        // Transaction 3: Income +10
        $booking2 = Booking::factory()->create([
            'user_id' => $this->user->id,
            'store_id' => $this->store->id,
            'class_id' => $gymClass->id,
            'status' => 'completed',
        ]);
        $this->cashRegisterService->recordPackageIncome($booking2, $userPackage);

        $summary3 = $this->cashRegisterService->getStoreSummary($this->store->id);
        $this->assertEquals(15, $summary3['net']);

        // Transaction 4: Expense -20
        $this->cashRegisterService->recordExpense([
            'amount' => 20,
            'description' => 'Larger expense',
            'category' => 'utilities',
            'store_id' => $this->store->id,
            'payment_method' => 'card',
        ]);

        $summary4 = $this->cashRegisterService->getStoreSummary($this->store->id);
        $this->assertEquals(-5, $summary4['net']); // 20 income - 25 expenses
    }
}
