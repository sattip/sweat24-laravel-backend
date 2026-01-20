<?php

namespace Tests\Unit\Models;

use App\Models\PaymentInstallment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentInstallmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_payment_installment(): void
    {
        $installment = PaymentInstallment::factory()->create();
        $this->assertDatabaseHas('payment_installments', ['id' => $installment->id]);
    }

    public function test_pending_by_default(): void
    {
        $installment = PaymentInstallment::factory()->create();
        $this->assertEquals('pending', $installment->status);
    }

    public function test_paid_state(): void
    {
        $installment = PaymentInstallment::factory()->paid()->create();

        $this->assertEquals('paid', $installment->status);
        $this->assertNotNull($installment->paid_date);
        $this->assertNotNull($installment->payment_method);
    }

    public function test_installment_number_is_set(): void
    {
        $installment = PaymentInstallment::factory()->create();

        $this->assertNotNull($installment->installment_number);
        $this->assertNotNull($installment->total_installments);
        $this->assertLessThanOrEqual($installment->total_installments, $installment->installment_number);
    }

    public function test_amount_is_positive(): void
    {
        $installment = PaymentInstallment::factory()->create();
        $this->assertGreaterThan(0, $installment->amount);
    }
}
