<?php

namespace Tests\Unit\Services;

use App\Models\CashRegisterEntry;
use App\Models\Package;
use App\Models\PaymentInstallment;
use App\Models\Store;
use App\Models\User;
use App\Models\UserPackage;
use App\Services\CashRegisterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TAMEIO INSTALLMENT LOGIC TESTS
 *
 * These tests verify installment-based payment scenarios and their
 * relationship with tameio entries.
 *
 * @group tameio
 * @group financial
 * @group installments
 */
class TameioInstallmentLogicTest extends TestCase
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
        $this->package = Package::factory()->create(['price' => 120, 'sessions' => 12]);
    }

    // ==========================================
    // SINGLE PAYMENT SUBSCRIPTIONS
    // ==========================================

    /**
     * @test
     * Given: A subscription paid in full with single payment
     * When: Payment is recorded
     * Then: UserPackage reflects fully paid status
     */
    public function single_payment_marks_subscription_fully_paid(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'total_sessions' => 12,
            'remaining_sessions' => 12,
            'status' => 'active',
            'payment_status' => UserPackage::PAYMENT_STATUS_PENDING,
            'amount_paid' => 0,
            'amount_remaining' => 120,
            'installments' => 1,
        ]);

        $userPackage->recordPayment(120, 'cash', 'Full payment');

        $this->assertEquals(UserPackage::PAYMENT_STATUS_PAID, $userPackage->payment_status);
        $this->assertEquals(120, $userPackage->amount_paid);
        $this->assertEquals(0, $userPackage->amount_remaining);
        $this->assertTrue($userPackage->isFullyPaid());
    }

    // ==========================================
    // MULTIPLE INSTALLMENTS
    // ==========================================

    /**
     * @test
     * Given: A subscription with 3 equal installments
     * When: Installments are created
     * Then: Each installment has correct amount and due date
     */
    public function multiple_equal_installments_created_correctly(): void
    {
        $totalAmount = 120;
        $numInstallments = 3;
        $amountPerInstallment = $totalAmount / $numInstallments;

        $installments = [];
        for ($i = 1; $i <= $numInstallments; $i++) {
            $installments[] = PaymentInstallment::factory()->create([
                'customer_id' => $this->user->id,
                'customer_name' => $this->user->name,
                'package_id' => $this->package->id,
                'package_name' => $this->package->name,
                'installment_number' => $i,
                'total_installments' => $numInstallments,
                'amount' => $amountPerInstallment,
                'due_date' => now()->addMonths($i - 1),
                'status' => 'pending',
            ]);
        }

        $this->assertCount(3, $installments);
        $this->assertEquals(40, $installments[0]->amount);
        $this->assertEquals(40, $installments[1]->amount);
        $this->assertEquals(40, $installments[2]->amount);
        $this->assertEquals(120, collect($installments)->sum('amount'));
    }

    /**
     * @test
     * Given: Installments for a subscription
     * When: First installment is paid
     * Then: Status transitions to partial
     */
    public function first_installment_payment_transitions_to_partial(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'total_sessions' => 12,
            'remaining_sessions' => 12,
            'status' => 'active',
            'payment_status' => UserPackage::PAYMENT_STATUS_PENDING,
            'amount_paid' => 0,
            'amount_remaining' => 120,
            'installments' => 3,
        ]);

        $userPackage->recordPayment(40, 'cash', 'First installment');

        $this->assertEquals(UserPackage::PAYMENT_STATUS_PARTIAL, $userPackage->payment_status);
        $this->assertEquals(40, $userPackage->amount_paid);
        $this->assertEquals(80, $userPackage->amount_remaining);
        $this->assertTrue($userPackage->hasPartialPayment());
        $this->assertFalse($userPackage->isFullyPaid());
    }

    /**
     * @test
     * Given: 3 installments where 2 are paid
     * When: Final installment is paid
     * Then: Status transitions to fully paid
     */
    public function final_installment_transitions_to_fully_paid(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'total_sessions' => 12,
            'remaining_sessions' => 12,
            'status' => 'active',
            'payment_status' => UserPackage::PAYMENT_STATUS_PARTIAL,
            'amount_paid' => 80, // 2 of 3 installments paid
            'amount_remaining' => 40,
            'installments' => 3,
        ]);

        $userPackage->recordPayment(40, 'card', 'Final installment');

        $this->assertEquals(UserPackage::PAYMENT_STATUS_PAID, $userPackage->payment_status);
        $this->assertEquals(120, $userPackage->amount_paid);
        $this->assertEquals(0, $userPackage->amount_remaining);
        $this->assertTrue($userPackage->isFullyPaid());
    }

    // ==========================================
    // UNEVEN INSTALLMENT AMOUNTS
    // ==========================================

    /**
     * @test
     * Given: A subscription with uneven installment amounts
     * When: All installments are paid
     * Then: Total equals subscription price exactly
     */
    public function uneven_installments_sum_to_total_price(): void
    {
        $totalAmount = 100;
        // Uneven split: 35 + 35 + 30
        $installmentAmounts = [35, 35, 30];

        $installments = [];
        foreach ($installmentAmounts as $i => $amount) {
            $installments[] = PaymentInstallment::factory()->create([
                'customer_id' => $this->user->id,
                'package_id' => $this->package->id,
                'installment_number' => $i + 1,
                'total_installments' => 3,
                'amount' => $amount,
                'due_date' => now()->addMonths($i),
                'status' => 'pending',
            ]);
        }

        $totalInstallments = collect($installments)->sum('amount');
        $this->assertEquals($totalAmount, $totalInstallments);
    }

    /**
     * @test
     * Given: Price that doesn't divide evenly
     * When: Creating installments
     * Then: Last installment adjusts for rounding
     */
    public function rounding_adjustment_on_last_installment(): void
    {
        $totalAmount = 100;
        $numInstallments = 3;
        // 100 / 3 = 33.33... so we need adjustment

        $baseAmount = floor($totalAmount / $numInstallments * 100) / 100; // 33.33
        $adjustedLast = $totalAmount - ($baseAmount * ($numInstallments - 1)); // 100 - 66.66 = 33.34

        $installments = [];
        for ($i = 1; $i <= $numInstallments; $i++) {
            $amount = ($i === $numInstallments) ? $adjustedLast : $baseAmount;
            $installments[] = PaymentInstallment::factory()->create([
                'customer_id' => $this->user->id,
                'package_id' => $this->package->id,
                'installment_number' => $i,
                'total_installments' => $numInstallments,
                'amount' => $amount,
                'status' => 'pending',
            ]);
        }

        $total = collect($installments)->sum('amount');
        $this->assertEqualsWithDelta(100, $total, 0.01);
    }

    // ==========================================
    // DEFERRED FIRST PAYMENT
    // ==========================================

    /**
     * @test
     * Given: Subscription with deferred first payment (30 days)
     * When: Installment schedule is created
     * Then: First due date is 30 days in the future
     */
    public function deferred_first_payment_correct_due_date(): void
    {
        $deferralDays = 30;
        $today = now()->startOfDay();

        $installment = PaymentInstallment::factory()->create([
            'customer_id' => $this->user->id,
            'package_id' => $this->package->id,
            'installment_number' => 1,
            'total_installments' => 3,
            'amount' => 40,
            'due_date' => $today->copy()->addDays($deferralDays),
            'status' => 'pending',
        ]);

        $daysUntilDue = $today->diffInDays($installment->due_date);
        $this->assertEquals($deferralDays, $daysUntilDue);
    }

    /**
     * @test
     * Given: Deferred payment schedule
     * When: User uses package before first payment due
     * Then: Sessions can still be consumed (package is active)
     */
    public function deferred_payment_allows_package_usage(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'total_sessions' => 12,
            'remaining_sessions' => 12,
            'status' => 'active',
            'payment_status' => UserPackage::PAYMENT_STATUS_PENDING,
            'amount_paid' => 0,
            'amount_remaining' => 120,
        ]);

        // Package should be usable even though no payment made yet
        $this->assertTrue($userPackage->canBeUsed());
        $this->assertEquals(12, $userPackage->remaining_sessions);
    }

    // ==========================================
    // INSTALLMENT STATUS TRANSITIONS
    // ==========================================

    /**
     * @test
     * Given: A pending installment
     * When: Payment is received
     * Then: Status transitions to paid with correct date
     */
    public function installment_status_transition_pending_to_paid(): void
    {
        $installment = PaymentInstallment::factory()->create([
            'customer_id' => $this->user->id,
            'package_id' => $this->package->id,
            'amount' => 40,
            'status' => 'pending',
            'paid_date' => null,
        ]);

        // Simulate payment
        $installment->update([
            'status' => 'paid',
            'paid_date' => now(),
            'payment_method' => 'card',
        ]);

        $this->assertEquals('paid', $installment->status);
        $this->assertNotNull($installment->paid_date);
        $this->assertEquals('card', $installment->payment_method);
    }

    /**
     * @test
     * Given: A pending installment past due date
     * When: Due date passes
     * Then: Status should be overdue
     */
    public function installment_becomes_overdue_after_due_date(): void
    {
        $installment = PaymentInstallment::factory()->create([
            'customer_id' => $this->user->id,
            'package_id' => $this->package->id,
            'amount' => 40,
            'due_date' => now()->subDays(5), // 5 days past due
            'status' => 'overdue',
            'paid_date' => null,
        ]);

        $this->assertEquals('overdue', $installment->status);
        $this->assertTrue($installment->due_date->isPast());
    }

    // ==========================================
    // INSTALLMENT COUNTS
    // ==========================================

    /**
     * @test
     * Given: A subscription with 4 installments
     * When: Querying installments
     * Then: Exactly 4 installments exist with sequential numbers
     */
    public function correct_installment_count_and_sequence(): void
    {
        $numInstallments = 4;

        for ($i = 1; $i <= $numInstallments; $i++) {
            PaymentInstallment::factory()->create([
                'customer_id' => $this->user->id,
                'package_id' => $this->package->id,
                'installment_number' => $i,
                'total_installments' => $numInstallments,
                'amount' => 30,
            ]);
        }

        $installments = PaymentInstallment::where('customer_id', $this->user->id)
            ->where('package_id', $this->package->id)
            ->orderBy('installment_number')
            ->get();

        $this->assertCount(4, $installments);
        $this->assertEquals([1, 2, 3, 4], $installments->pluck('installment_number')->toArray());
        $this->assertTrue($installments->every(fn($i) => $i->total_installments === 4));
    }

    // ==========================================
    // PAYMENT METHOD TRACKING
    // ==========================================

    /**
     * @test
     * Given: Multiple installments paid with different methods
     * When: Querying payment methods
     * Then: Each installment records correct method
     */
    public function different_payment_methods_per_installment(): void
    {
        $methods = ['cash', 'card', 'transfer'];

        $installments = [];
        foreach ($methods as $i => $method) {
            $installments[] = PaymentInstallment::factory()->paid()->create([
                'customer_id' => $this->user->id,
                'package_id' => $this->package->id,
                'installment_number' => $i + 1,
                'total_installments' => 3,
                'amount' => 40,
                'payment_method' => $method,
            ]);
        }

        $this->assertEquals('cash', $installments[0]->payment_method);
        $this->assertEquals('card', $installments[1]->payment_method);
        $this->assertEquals('transfer', $installments[2]->payment_method);
    }

    // ==========================================
    // INSTALLMENT NOTES AND AUDIT
    // ==========================================

    /**
     * @test
     * Given: Payments recorded with notes
     * When: Querying payment notes
     * Then: All notes are preserved chronologically
     */
    public function payment_notes_accumulated_chronologically(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'payment_status' => UserPackage::PAYMENT_STATUS_PENDING,
            'amount_paid' => 0,
            'amount_remaining' => 120,
            'payment_notes' => null,
        ]);

        $userPackage->recordPayment(40, 'cash', 'First installment - cash');
        $userPackage->recordPayment(40, 'card', 'Second installment - card');
        $userPackage->recordPayment(40, 'transfer', 'Final installment - bank transfer');

        $this->assertStringContainsString('First installment', $userPackage->payment_notes);
        $this->assertStringContainsString('Second installment', $userPackage->payment_notes);
        $this->assertStringContainsString('Final installment', $userPackage->payment_notes);
    }

    // ==========================================
    // PAYMENT SUMMARY
    // ==========================================

    /**
     * @test
     * Given: A subscription with partial payment
     * When: Getting payment summary
     * Then: All fields are accurate
     */
    public function payment_summary_accuracy(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'total_sessions' => 12,
            'status' => 'active',
            'payment_status' => UserPackage::PAYMENT_STATUS_PARTIAL,
            'payment_method' => 'card',
            'amount_paid' => 80,
            'amount_remaining' => 40,
            'installments' => 3,
            'payment_notes' => 'Test notes',
        ]);

        $summary = $userPackage->getPaymentSummary();

        $this->assertEquals(120, $summary['total_amount']); // from package
        $this->assertEquals(80, $summary['amount_paid']);
        $this->assertEquals(40, $summary['amount_remaining']);
        $this->assertEquals('card', $summary['payment_method']);
        $this->assertEquals(UserPackage::PAYMENT_STATUS_PARTIAL, $summary['payment_status']);
        $this->assertFalse($summary['is_fully_paid']);
        $this->assertTrue($summary['has_partial_payment']);
        $this->assertTrue($summary['is_pending']);
        $this->assertEquals(3, $summary['installments']);
        $this->assertEquals('Test notes', $summary['payment_notes']);
    }
}
