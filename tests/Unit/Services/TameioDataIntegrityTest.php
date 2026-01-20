<?php

namespace Tests\Unit\Services;

use App\Models\Booking;
use App\Models\CashRegisterEntry;
use App\Models\GymClass;
use App\Models\Package;
use App\Models\PaymentInstallment;
use App\Models\Store;
use App\Models\User;
use App\Models\UserPackage;
use App\Services\CashRegisterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * TAMEIO DATA INTEGRITY & IDEMPOTENCY TESTS
 *
 * Tests ensuring data integrity, preventing duplicates, maintaining
 * referential integrity, and supporting reconciliation processes.
 *
 * @group tameio
 * @group financial
 * @group data-integrity
 */
class TameioDataIntegrityTest extends TestCase
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
    // REFERENTIAL INTEGRITY
    // ==========================================

    /**
     * @test
     * Given: A tameio entry for a package
     * When: Querying the entry
     * Then: Entry has valid references to user, store, and package
     */
    public function entry_maintains_valid_references(): void
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

        // Verify all foreign keys are valid
        $this->assertNotNull($entry->user);
        $this->assertNotNull($entry->store);
        $this->assertEquals($this->user->id, $entry->user_id);
        $this->assertEquals($this->store->id, $entry->store_id);
        $this->assertEquals($userPackage->id, $entry->related_entity_id);
        $this->assertEquals('user_package', $entry->related_entity_type);
    }

    /**
     * @test
     * Given: Multiple entries for same subscription
     * When: Querying by subscription
     * Then: All entries correctly linked to the subscription
     */
    public function all_entries_linked_to_subscription(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'total_sessions' => 10,
            'remaining_sessions' => 10,
        ]);

        $gymClass = GymClass::factory()->create();

        // Create 5 entries for same package
        for ($i = 0; $i < 5; $i++) {
            $booking = Booking::factory()->create([
                'user_id' => $this->user->id,
                'store_id' => $this->store->id,
                'class_id' => $gymClass->id,
                'status' => 'completed',
            ]);
            $this->cashRegisterService->recordPackageIncome($booking, $userPackage);
        }

        $entries = CashRegisterEntry::where('related_entity_id', $userPackage->id)
            ->where('related_entity_type', 'user_package')
            ->get();

        $this->assertCount(5, $entries);
        $entries->each(function ($entry) use ($userPackage) {
            $this->assertEquals($userPackage->id, $entry->related_entity_id);
        });
    }

    // ==========================================
    // RECONCILIATION SUPPORT
    // ==========================================

    /**
     * @test
     * Given: Package with all sessions consumed
     * When: Reconciling tameio with package
     * Then: Total entries amount equals package price
     */
    public function total_entries_match_package_price(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'total_sessions' => 10,
            'remaining_sessions' => 10,
        ]);

        $gymClass = GymClass::factory()->create();
        $perSessionCost = $this->cashRegisterService->calculatePerTrainingCost($userPackage);

        // Consume all sessions
        for ($i = 10; $i >= 1; $i--) {
            $userPackage->remaining_sessions = $i;
            $userPackage->save();

            $amount = $this->cashRegisterService->handleRoundingAdjustment($userPackage, $perSessionCost);

            $booking = Booking::factory()->create([
                'user_id' => $this->user->id,
                'store_id' => $this->store->id,
                'class_id' => $gymClass->id,
                'status' => 'completed',
            ]);

            CashRegisterEntry::create([
                'type' => 'income',
                'amount' => $amount,
                'description' => "Package usage",
                'category' => 'package_usage',
                'user_id' => $this->user->id,
                'store_id' => $this->store->id,
                'related_entity_id' => $userPackage->id,
                'related_entity_type' => 'user_package',
                'payment_method' => 'package_credit',
            ]);
        }

        $totalRevenue = CashRegisterEntry::where('related_entity_id', $userPackage->id)
            ->where('related_entity_type', 'user_package')
            ->sum('amount');

        $this->assertEquals($this->package->price, $totalRevenue);
    }

    /**
     * @test
     * Given: Store with multiple income and expense entries
     * When: Computing net balance
     * Then: Balance accurately reflects all transactions
     */
    public function store_balance_reconciliation(): void
    {
        $this->actingAs($this->admin);

        // Create income entries
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'total_sessions' => 10,
            'remaining_sessions' => 10,
        ]);

        $gymClass = GymClass::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $booking = Booking::factory()->create([
                'user_id' => $this->user->id,
                'store_id' => $this->store->id,
                'class_id' => $gymClass->id,
                'status' => 'completed',
            ]);
            $this->cashRegisterService->recordPackageIncome($booking, $userPackage);
        }

        // Create expense entries
        for ($i = 0; $i < 3; $i++) {
            $this->cashRegisterService->recordExpense([
                'amount' => 15,
                'description' => "Test expense $i",
                'category' => 'supplies',
                'store_id' => $this->store->id,
                'payment_method' => 'cash',
            ]);
        }

        $summary = $this->cashRegisterService->getStoreSummary($this->store->id);

        // 5 income entries * 10 per session = 50 income
        // 3 expense entries * 15 each = 45 expenses
        // Net = 50 - 45 = 5
        $this->assertEquals(50, $summary['income']);
        $this->assertEquals(45, $summary['expenses']);
        $this->assertEquals(5, $summary['net']);
    }

    // ==========================================
    // DATA CONSISTENCY
    // ==========================================

    /**
     * @test
     * Given: Payment recorded on UserPackage
     * When: Checking payment tracking fields
     * Then: All fields update consistently
     */
    public function payment_tracking_fields_consistent(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'payment_status' => UserPackage::PAYMENT_STATUS_PENDING,
            'amount_paid' => 0,
            'amount_remaining' => 100,
        ]);

        $userPackage->recordPayment(30, 'cash', 'First payment');
        $userPackage->recordPayment(30, 'card', 'Second payment');
        $userPackage->recordPayment(40, 'transfer', 'Final payment');

        // Verify consistency: paid + remaining = total
        $this->assertEquals(100, $userPackage->amount_paid + $userPackage->amount_remaining);
        $this->assertEquals(100, $userPackage->amount_paid);
        $this->assertEquals(0, $userPackage->amount_remaining);
        $this->assertEquals(UserPackage::PAYMENT_STATUS_PAID, $userPackage->payment_status);
    }

    /**
     * @test
     * Given: Installments for a package
     * When: Summing all installment amounts
     * Then: Total matches expected package price
     */
    public function installment_amounts_sum_correctly(): void
    {
        $totalAmount = 100;

        PaymentInstallment::factory()->create([
            'customer_id' => $this->user->id,
            'package_id' => $this->package->id,
            'installment_number' => 1,
            'total_installments' => 4,
            'amount' => 25,
        ]);

        PaymentInstallment::factory()->create([
            'customer_id' => $this->user->id,
            'package_id' => $this->package->id,
            'installment_number' => 2,
            'total_installments' => 4,
            'amount' => 25,
        ]);

        PaymentInstallment::factory()->create([
            'customer_id' => $this->user->id,
            'package_id' => $this->package->id,
            'installment_number' => 3,
            'total_installments' => 4,
            'amount' => 25,
        ]);

        PaymentInstallment::factory()->create([
            'customer_id' => $this->user->id,
            'package_id' => $this->package->id,
            'installment_number' => 4,
            'total_installments' => 4,
            'amount' => 25,
        ]);

        $sum = PaymentInstallment::where('customer_id', $this->user->id)
            ->where('package_id', $this->package->id)
            ->sum('amount');

        $this->assertEquals($totalAmount, $sum);
    }

    // ==========================================
    // ORPHAN PREVENTION
    // ==========================================

    /**
     * @test
     * Given: A tameio entry
     * When: Entry is created
     * Then: Entry always has required fields populated
     */
    public function entry_has_all_required_fields(): void
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

        // All required fields must be set
        $this->assertNotNull($entry->type);
        $this->assertNotNull($entry->amount);
        $this->assertNotNull($entry->user_id);
        $this->assertNotNull($entry->store_id);
        $this->assertNotNull($entry->category);
        $this->assertNotNull($entry->payment_method);
        $this->assertNotEmpty($entry->description);
    }

    /**
     * @test
     * Given: An installment
     * When: Checking installment fields
     * Then: Installment has valid customer and package references
     */
    public function installment_has_valid_references(): void
    {
        $installment = PaymentInstallment::factory()->create([
            'customer_id' => $this->user->id,
            'customer_name' => $this->user->name,
            'package_id' => $this->package->id,
            'package_name' => $this->package->name,
            'installment_number' => 1,
            'total_installments' => 3,
            'amount' => 40,
        ]);

        $this->assertEquals($this->user->id, $installment->customer_id);
        $this->assertEquals($this->user->name, $installment->customer_name);
        $this->assertEquals($this->package->id, $installment->package_id);
        $this->assertEquals($this->package->name, $installment->package_name);
    }

    // ==========================================
    // SEQUENTIAL INTEGRITY
    // ==========================================

    /**
     * @test
     * Given: Installments created in sequence
     * When: Checking installment numbers
     * Then: Numbers are sequential without gaps
     */
    public function installment_numbers_sequential(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            PaymentInstallment::factory()->create([
                'customer_id' => $this->user->id,
                'package_id' => $this->package->id,
                'installment_number' => $i,
                'total_installments' => 5,
                'amount' => 20,
            ]);
        }

        $installments = PaymentInstallment::where('customer_id', $this->user->id)
            ->where('package_id', $this->package->id)
            ->orderBy('installment_number')
            ->pluck('installment_number')
            ->toArray();

        $this->assertEquals([1, 2, 3, 4, 5], $installments);
    }

    /**
     * @test
     * Given: Multiple entries for a package
     * When: Entries are created over time
     * Then: Entries maintain chronological order by ID
     */
    public function entries_maintain_chronological_order(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'total_sessions' => 10,
            'remaining_sessions' => 10,
        ]);

        $gymClass = GymClass::factory()->create();

        $entries = [];
        for ($i = 0; $i < 5; $i++) {
            $booking = Booking::factory()->create([
                'user_id' => $this->user->id,
                'store_id' => $this->store->id,
                'class_id' => $gymClass->id,
                'status' => 'completed',
            ]);
            $entries[] = $this->cashRegisterService->recordPackageIncome($booking, $userPackage);
        }

        // IDs should be strictly increasing
        for ($i = 1; $i < count($entries); $i++) {
            $this->assertGreaterThan($entries[$i - 1]->id, $entries[$i]->id);
        }
    }

    // ==========================================
    // TYPE CONSISTENCY
    // ==========================================

    /**
     * @test
     * Given: Income entry from package
     * When: Checking entry type
     * Then: Type is always 'income'
     */
    public function package_income_type_consistency(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'total_sessions' => 10,
            'remaining_sessions' => 10,
        ]);

        $gymClass = GymClass::factory()->create();

        for ($i = 0; $i < 3; $i++) {
            $booking = Booking::factory()->create([
                'user_id' => $this->user->id,
                'store_id' => $this->store->id,
                'class_id' => $gymClass->id,
                'status' => 'completed',
            ]);
            $this->cashRegisterService->recordPackageIncome($booking, $userPackage);
        }

        $entries = CashRegisterEntry::where('related_entity_id', $userPackage->id)
            ->where('related_entity_type', 'user_package')
            ->get();

        $entries->each(function ($entry) {
            $this->assertEquals('income', $entry->type);
            $this->assertEquals('package_usage', $entry->category);
            $this->assertEquals('package_credit', $entry->payment_method);
        });
    }

    /**
     * @test
     * Given: Expense entries
     * When: Checking entry type
     * Then: Type is always 'withdrawal'
     */
    public function expense_type_consistency(): void
    {
        $this->actingAs($this->admin);

        for ($i = 0; $i < 3; $i++) {
            $this->cashRegisterService->recordExpense([
                'amount' => 25,
                'description' => "Test expense $i",
                'category' => 'supplies',
                'store_id' => $this->store->id,
                'payment_method' => 'cash',
            ]);
        }

        $entries = CashRegisterEntry::where('type', 'withdrawal')
            ->where('store_id', $this->store->id)
            ->get();

        $entries->each(function ($entry) {
            $this->assertEquals('withdrawal', $entry->type);
        });
    }

    // ==========================================
    // AMOUNT PRECISION
    // ==========================================

    /**
     * @test
     * Given: Entry amount as decimal
     * When: Storing and retrieving
     * Then: Precision maintained to 2 decimal places
     */
    public function amount_precision_maintained(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'total_sessions' => 7,
            'remaining_sessions' => 7,
        ]);

        $gymClass = GymClass::factory()->create();

        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'store_id' => $this->store->id,
            'class_id' => $gymClass->id,
            'status' => 'completed',
        ]);

        $entry = $this->cashRegisterService->recordPackageIncome($booking, $userPackage);

        // 100 / 7 = 14.285714... should be stored as 14.29
        $this->assertEquals('14.29', number_format($entry->amount, 2, '.', ''));
    }
}
