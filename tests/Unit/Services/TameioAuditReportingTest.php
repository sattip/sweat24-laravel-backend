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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TAMEIO AUDIT & REPORTING READINESS TESTS
 *
 * Tests ensuring audit trail completeness, traceability,
 * and report generation capabilities for financial compliance.
 *
 * @group tameio
 * @group financial
 * @group audit
 */
class TameioAuditReportingTest extends TestCase
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
    // TRACEABILITY
    // ==========================================

    /**
     * @test
     * Given: A tameio entry
     * When: Auditing the entry
     * Then: Entry can be traced back to source subscription
     */
    public function entry_traceable_to_subscription(): void
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

        // Entry has traceable reference to source
        $this->assertEquals($userPackage->id, $entry->related_entity_id);
        $this->assertEquals('user_package', $entry->related_entity_type);

        // Can reconstruct the chain: entry → user_package → package → user
        $tracedPackage = UserPackage::find($entry->related_entity_id);
        $this->assertNotNull($tracedPackage);
        $this->assertEquals($this->user->id, $tracedPackage->user_id);
        $this->assertEquals($this->package->id, $tracedPackage->package_id);
    }

    /**
     * @test
     * Given: An entry
     * When: Querying audit fields
     * Then: Entry has user, store, and timestamp information
     */
    public function entry_has_complete_audit_trail(): void
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

        // Audit trail fields present
        $this->assertNotNull($entry->user_id);
        $this->assertNotNull($entry->store_id);
        $this->assertNotNull($entry->created_at);
        $this->assertNotNull($entry->updated_at);
        $this->assertNotEmpty($entry->description);
    }

    /**
     * @test
     * Given: Multiple entries for same user
     * When: Querying by user
     * Then: All user's transactions retrievable
     */
    public function user_transaction_history_complete(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'total_sessions' => 10,
            'remaining_sessions' => 10,
        ]);

        $gymClass = GymClass::factory()->create();

        // Create 5 entries for the user
        for ($i = 0; $i < 5; $i++) {
            $booking = Booking::factory()->create([
                'user_id' => $this->user->id,
                'store_id' => $this->store->id,
                'class_id' => $gymClass->id,
                'status' => 'completed',
            ]);
            $this->cashRegisterService->recordPackageIncome($booking, $userPackage);
        }

        $userEntries = CashRegisterEntry::where('user_id', $this->user->id)->get();

        $this->assertCount(5, $userEntries);
        $userEntries->each(function ($entry) {
            $this->assertEquals($this->user->id, $entry->user_id);
        });
    }

    // ==========================================
    // IMMUTABLE REFERENCES
    // ==========================================

    /**
     * @test
     * Given: Entry with related entity
     * When: Original entity data changes
     * Then: Entry's description preserves original context
     */
    public function entry_preserves_original_context(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'name' => 'Original Package Name',
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

        // Entry description contains package name at time of creation
        $this->assertStringContainsString('Original Package Name', $entry->description);

        // Even if package name changes, entry description is unchanged
        $userPackage->update(['name' => 'Modified Package Name']);

        $entry->refresh();
        $this->assertStringContainsString('Original Package Name', $entry->description);
    }

    /**
     * @test
     * Given: Installment payment
     * When: Customer name changes
     * Then: Installment preserves original customer name
     */
    public function installment_preserves_customer_snapshot(): void
    {
        $originalName = $this->user->name;

        $installment = PaymentInstallment::factory()->create([
            'customer_id' => $this->user->id,
            'customer_name' => $originalName,
            'package_id' => $this->package->id,
            'package_name' => $this->package->name,
            'amount' => 40,
        ]);

        // Customer name changes
        $this->user->update(['name' => 'New Customer Name']);

        // Installment still has original name
        $installment->refresh();
        $this->assertEquals($originalName, $installment->customer_name);
    }

    // ==========================================
    // PACKAGE CONSUMPTION REPORTING
    // ==========================================

    /**
     * @test
     * Given: Package with partial consumption
     * When: Generating consumption report
     * Then: Report shows accurate usage statistics
     */
    public function consumption_report_accurate(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'name' => $this->package->name,
            'total_sessions' => 10,
            'remaining_sessions' => 4, // 6 used
        ]);

        $report = $this->cashRegisterService->getPackageConsumptionReport($userPackage->id);

        $this->assertEquals($userPackage->id, $report['user_package_id']);
        $this->assertEquals($this->package->name, $report['package_name']);
        $this->assertEquals($this->user->name, $report['user_name']);
        $this->assertEquals(10, $report['total_sessions']);
        $this->assertEquals(6, $report['used_sessions']);
        $this->assertEquals(4, $report['remaining_sessions']);
        $this->assertEquals(10, $report['per_training_cost']);
        $this->assertEquals(60, $report['total_revenue']); // 6 * 10
    }

    /**
     * @test
     * Given: Fully consumed package
     * When: Generating consumption report
     * Then: Report shows 100% utilization
     */
    public function fully_consumed_package_report(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'name' => $this->package->name,
            'total_sessions' => 10,
            'remaining_sessions' => 0, // All used
        ]);

        $report = $this->cashRegisterService->getPackageConsumptionReport($userPackage->id);

        $this->assertEquals(10, $report['used_sessions']);
        $this->assertEquals(0, $report['remaining_sessions']);
        $this->assertEquals(100, $report['total_revenue']); // Full package price
    }

    // ==========================================
    // STORE SUMMARY REPORTING
    // ==========================================

    /**
     * @test
     * Given: Store with transactions
     * When: Generating store summary
     * Then: Summary contains all required fields
     */
    public function store_summary_complete(): void
    {
        $this->actingAs($this->admin);

        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'total_sessions' => 10,
            'remaining_sessions' => 10,
        ]);

        $gymClass = GymClass::factory()->create();

        // Create income
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'store_id' => $this->store->id,
            'class_id' => $gymClass->id,
            'status' => 'completed',
        ]);
        $this->cashRegisterService->recordPackageIncome($booking, $userPackage);

        // Create expense
        $this->cashRegisterService->recordExpense([
            'amount' => 5,
            'description' => 'Test expense',
            'category' => 'supplies',
            'store_id' => $this->store->id,
            'payment_method' => 'cash',
        ]);

        $summary = $this->cashRegisterService->getStoreSummary($this->store->id);

        // Required fields present
        $this->assertArrayHasKey('store_id', $summary);
        $this->assertArrayHasKey('income', $summary);
        $this->assertArrayHasKey('expenses', $summary);
        $this->assertArrayHasKey('net', $summary);
        $this->assertArrayHasKey('entries_count', $summary);

        // Values accurate
        $this->assertEquals($this->store->id, $summary['store_id']);
        $this->assertEquals(10, $summary['income']);
        $this->assertEquals(5, $summary['expenses']);
        $this->assertEquals(5, $summary['net']);
        $this->assertEquals(2, $summary['entries_count']);
    }

    /**
     * @test
     * Given: Store with no transactions
     * When: Generating store summary
     * Then: Summary shows zero values
     */
    public function empty_store_summary(): void
    {
        $emptyStore = Store::factory()->create();

        $summary = $this->cashRegisterService->getStoreSummary($emptyStore->id);

        $this->assertEquals($emptyStore->id, $summary['store_id']);
        $this->assertEquals(0, $summary['income']);
        $this->assertEquals(0, $summary['expenses']);
        $this->assertEquals(0, $summary['net']);
        $this->assertEquals(0, $summary['entries_count']);
    }

    // ==========================================
    // INSTALLMENT PAYMENT TRACKING
    // ==========================================

    /**
     * @test
     * Given: Subscription with installment plan
     * When: Auditing payments
     * Then: Complete installment history available
     */
    public function installment_history_complete(): void
    {
        // Create 4 installments with different statuses
        PaymentInstallment::factory()->paid()->create([
            'customer_id' => $this->user->id,
            'package_id' => $this->package->id,
            'installment_number' => 1,
            'total_installments' => 4,
            'amount' => 25,
            'due_date' => now()->subMonths(3),
            'paid_date' => now()->subMonths(3),
            'payment_method' => 'cash',
        ]);

        PaymentInstallment::factory()->paid()->create([
            'customer_id' => $this->user->id,
            'package_id' => $this->package->id,
            'installment_number' => 2,
            'total_installments' => 4,
            'amount' => 25,
            'due_date' => now()->subMonths(2),
            'paid_date' => now()->subMonths(2)->addDays(5),
            'payment_method' => 'card',
        ]);

        PaymentInstallment::factory()->create([
            'customer_id' => $this->user->id,
            'package_id' => $this->package->id,
            'installment_number' => 3,
            'total_installments' => 4,
            'amount' => 25,
            'due_date' => now()->subMonth(),
            'status' => 'overdue',
        ]);

        PaymentInstallment::factory()->create([
            'customer_id' => $this->user->id,
            'package_id' => $this->package->id,
            'installment_number' => 4,
            'total_installments' => 4,
            'amount' => 25,
            'due_date' => now()->addMonth(),
            'status' => 'pending',
        ]);

        $installments = PaymentInstallment::where('customer_id', $this->user->id)
            ->where('package_id', $this->package->id)
            ->orderBy('installment_number')
            ->get();

        // Complete audit trail
        $this->assertCount(4, $installments);
        $this->assertEquals('paid', $installments[0]->status);
        $this->assertEquals('paid', $installments[1]->status);
        $this->assertEquals('overdue', $installments[2]->status);
        $this->assertEquals('pending', $installments[3]->status);

        // Payment methods recorded for paid installments
        $this->assertEquals('cash', $installments[0]->payment_method);
        $this->assertEquals('card', $installments[1]->payment_method);
    }

    /**
     * @test
     * Given: Paid installments
     * When: Auditing payment dates
     * Then: Both due date and actual paid date available
     */
    public function installment_dates_tracked(): void
    {
        $dueDate = now()->subDays(10);
        $paidDate = now()->subDays(5); // Paid 5 days late

        $installment = PaymentInstallment::factory()->paid()->create([
            'customer_id' => $this->user->id,
            'package_id' => $this->package->id,
            'due_date' => $dueDate,
            'paid_date' => $paidDate,
        ]);

        // Both dates available for audit
        $this->assertEquals($dueDate->toDateString(), $installment->due_date->toDateString());
        $this->assertEquals($paidDate->toDateString(), $installment->paid_date->toDateString());

        // Can calculate payment delay
        $delayDays = $installment->due_date->diffInDays($installment->paid_date, false);
        $this->assertEquals(5, $delayDays);
    }

    // ==========================================
    // USER PACKAGE PAYMENT HISTORY
    // ==========================================

    /**
     * @test
     * Given: Subscription with multiple payments
     * When: Reviewing payment notes
     * Then: Complete payment history in notes
     */
    public function payment_notes_maintain_history(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'payment_status' => UserPackage::PAYMENT_STATUS_PENDING,
            'amount_paid' => 0,
            'amount_remaining' => 100,
            'payment_notes' => null,
        ]);

        $userPackage->recordPayment(30, 'cash', 'Payment 1 - Cash at front desk');
        $userPackage->recordPayment(30, 'card', 'Payment 2 - Card ending 4242');
        $userPackage->recordPayment(40, 'transfer', 'Payment 3 - Bank transfer ref: TRF123');

        // All payment notes preserved
        $this->assertStringContainsString('Payment 1', $userPackage->payment_notes);
        $this->assertStringContainsString('Payment 2', $userPackage->payment_notes);
        $this->assertStringContainsString('Payment 3', $userPackage->payment_notes);
        $this->assertStringContainsString('Cash at front desk', $userPackage->payment_notes);
        $this->assertStringContainsString('4242', $userPackage->payment_notes);
        $this->assertStringContainsString('TRF123', $userPackage->payment_notes);
    }

    /**
     * @test
     * Given: Subscription payment summary
     * When: Auditing payment status
     * Then: Summary reflects accurate financial state
     */
    public function payment_summary_audit_ready(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'total_sessions' => 10,
            'payment_status' => UserPackage::PAYMENT_STATUS_PARTIAL,
            'payment_method' => 'mixed',
            'amount_paid' => 60,
            'amount_remaining' => 40,
            'installments' => 4,
            'payment_notes' => 'Installment 1: cash, Installment 2: card',
        ]);

        $summary = $userPackage->getPaymentSummary();

        // All audit-relevant fields present
        $this->assertEquals(100, $summary['total_amount']);
        $this->assertEquals(60, $summary['amount_paid']);
        $this->assertEquals(40, $summary['amount_remaining']);
        $this->assertEquals('mixed', $summary['payment_method']);
        $this->assertEquals(UserPackage::PAYMENT_STATUS_PARTIAL, $summary['payment_status']);
        $this->assertFalse($summary['is_fully_paid']);
        $this->assertTrue($summary['has_partial_payment']);
        $this->assertEquals(4, $summary['installments']);
        $this->assertNotEmpty($summary['payment_notes']);
    }

    // ==========================================
    // TIMESTAMP INTEGRITY
    // ==========================================

    /**
     * @test
     * Given: Entries created at different times
     * When: Sorting by timestamp
     * Then: Entries in correct chronological order
     */
    public function entries_maintain_chronological_timestamps(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'total_sessions' => 10,
            'remaining_sessions' => 10,
        ]);

        $gymClass = GymClass::factory()->create();

        Carbon::setTestNow(Carbon::parse('2024-01-01 10:00:00'));
        $booking1 = Booking::factory()->create([
            'user_id' => $this->user->id,
            'store_id' => $this->store->id,
            'class_id' => $gymClass->id,
            'status' => 'completed',
        ]);
        $entry1 = $this->cashRegisterService->recordPackageIncome($booking1, $userPackage);

        Carbon::setTestNow(Carbon::parse('2024-01-01 14:00:00'));
        $booking2 = Booking::factory()->create([
            'user_id' => $this->user->id,
            'store_id' => $this->store->id,
            'class_id' => $gymClass->id,
            'status' => 'completed',
        ]);
        $entry2 = $this->cashRegisterService->recordPackageIncome($booking2, $userPackage);

        Carbon::setTestNow(Carbon::parse('2024-01-02 09:00:00'));
        $booking3 = Booking::factory()->create([
            'user_id' => $this->user->id,
            'store_id' => $this->store->id,
            'class_id' => $gymClass->id,
            'status' => 'completed',
        ]);
        $entry3 = $this->cashRegisterService->recordPackageIncome($booking3, $userPackage);

        // Entries sorted chronologically
        $entries = CashRegisterEntry::where('related_entity_id', $userPackage->id)
            ->orderBy('created_at')
            ->get();

        $this->assertTrue($entries[0]->created_at->lt($entries[1]->created_at));
        $this->assertTrue($entries[1]->created_at->lt($entries[2]->created_at));

        Carbon::setTestNow();
    }

    /**
     * @test
     * Given: Entry record
     * When: Checking timestamps
     * Then: Both created_at and updated_at populated
     */
    public function entry_timestamps_populated(): void
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

        $this->assertNotNull($entry->created_at);
        $this->assertNotNull($entry->updated_at);
        $this->assertInstanceOf(Carbon::class, $entry->created_at);
        $this->assertInstanceOf(Carbon::class, $entry->updated_at);
    }
}
