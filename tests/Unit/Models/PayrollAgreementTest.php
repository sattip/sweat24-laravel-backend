<?php

namespace Tests\Unit\Models;

use App\Models\Instructor;
use App\Models\PayrollAgreement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollAgreementTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_payroll_agreement(): void
    {
        $agreement = PayrollAgreement::factory()->create();
        $this->assertDatabaseHas('payroll_agreements', ['id' => $agreement->id]);
    }

    public function test_belongs_to_instructor(): void
    {
        $instructor = Instructor::factory()->create();
        $agreement = PayrollAgreement::factory()->create(['instructor_id' => $instructor->id]);

        $this->assertInstanceOf(Instructor::class, $agreement->instructor);
        $this->assertEquals($instructor->id, $agreement->instructor->id);
    }

    public function test_hourly_rate_state(): void
    {
        $agreement = PayrollAgreement::factory()->hourlyRate()->create();
        $this->assertEquals('hourly_rate', $agreement->type);
    }

    public function test_bonus_state(): void
    {
        $agreement = PayrollAgreement::factory()->bonus()->create();
        $this->assertEquals('bonus', $agreement->type);
    }

    public function test_deduction_state(): void
    {
        $agreement = PayrollAgreement::factory()->deduction()->create();
        $this->assertEquals('deduction', $agreement->type);
    }

    public function test_special_rate_state(): void
    {
        $agreement = PayrollAgreement::factory()->specialRate()->create();
        $this->assertEquals('special_rate', $agreement->type);
    }

    public function test_recurring_state(): void
    {
        $agreement = PayrollAgreement::factory()->recurring()->create();
        $this->assertTrue($agreement->is_recurring);
    }

    public function test_active_state(): void
    {
        $agreement = PayrollAgreement::factory()->active()->create();
        $this->assertTrue($agreement->is_active);
    }

    public function test_inactive_state(): void
    {
        $agreement = PayrollAgreement::factory()->inactive()->create();
        $this->assertFalse($agreement->is_active);
    }

    public function test_with_end_date_state(): void
    {
        $agreement = PayrollAgreement::factory()->withEndDate()->create();
        $this->assertNotNull($agreement->end_date);
    }

    public function test_expired_state(): void
    {
        $agreement = PayrollAgreement::factory()->expired()->create();

        $this->assertFalse($agreement->is_active);
        $this->assertNotNull($agreement->end_date);
    }
}
