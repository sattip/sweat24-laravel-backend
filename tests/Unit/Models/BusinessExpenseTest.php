<?php

namespace Tests\Unit\Models;

use App\Models\BusinessExpense;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_business_expense(): void
    {
        $store = Store::factory()->create();

        $expense = BusinessExpense::factory()->create(['store_id' => $store->id]);

        $this->assertDatabaseHas('business_expenses', [
            'id' => $expense->id,
            'store_id' => $store->id,
        ]);
    }

    public function test_expense_belongs_to_store(): void
    {
        $store = Store::factory()->create();
        $expense = BusinessExpense::factory()->create(['store_id' => $store->id]);

        $this->assertInstanceOf(Store::class, $expense->store);
        $this->assertEquals($store->id, $expense->store->id);
    }

    public function test_expense_has_category_field(): void
    {
        $expense = BusinessExpense::factory()->create(['category' => 'utilities']);

        $this->assertEquals('utilities', $expense->category);
    }

    public function test_approved_state(): void
    {
        $expense = BusinessExpense::factory()->approved()->create();

        $this->assertTrue($expense->approved);
        $this->assertNotNull($expense->approved_by);
    }

    public function test_pending_state(): void
    {
        $expense = BusinessExpense::factory()->pending()->create();

        $this->assertFalse($expense->approved);
        $this->assertNull($expense->approved_by);
    }

    public function test_with_receipt_state(): void
    {
        $expense = BusinessExpense::factory()->withReceipt()->create();

        $this->assertNotNull($expense->receipt);
        $this->assertStringStartsWith('receipts/', $expense->receipt);
    }

    public function test_cash_state(): void
    {
        $expense = BusinessExpense::factory()->cash()->create();

        $this->assertEquals('cash', $expense->payment_method);
    }

    public function test_card_state(): void
    {
        $expense = BusinessExpense::factory()->card()->create();

        $this->assertEquals('card', $expense->payment_method);
    }

    public function test_amount_cast_to_decimal(): void
    {
        $expense = BusinessExpense::factory()->create(['amount' => 150.75]);

        $this->assertEquals('150.75', $expense->amount);
    }

    public function test_date_cast_to_date(): void
    {
        $expense = BusinessExpense::factory()->create(['date' => '2026-01-15']);

        $this->assertInstanceOf(\Carbon\Carbon::class, $expense->date);
        $this->assertEquals('2026-01-15', $expense->date->format('Y-m-d'));
    }

    public function test_approved_cast_to_boolean(): void
    {
        $expense = BusinessExpense::factory()->create(['approved' => true]);

        $this->assertIsBool($expense->approved);
        $this->assertTrue($expense->approved);
    }
}
