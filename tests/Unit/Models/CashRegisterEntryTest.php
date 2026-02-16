<?php

namespace Tests\Unit\Models;

use App\Models\CashRegisterEntry;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashRegisterEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_cash_register_entry(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create();

        $entry = CashRegisterEntry::factory()->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('cash_register_entries', [
            'id' => $entry->id,
            'store_id' => $store->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_entry_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $entry = CashRegisterEntry::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $entry->user);
        $this->assertEquals($user->id, $entry->user->id);
    }

    public function test_entry_belongs_to_store(): void
    {
        $store = Store::factory()->create();
        $entry = CashRegisterEntry::factory()->create(['store_id' => $store->id]);

        $this->assertInstanceOf(Store::class, $entry->store);
        $this->assertEquals($store->id, $entry->store->id);
    }

    public function test_income_state(): void
    {
        $entry = CashRegisterEntry::factory()->income()->create();

        $this->assertEquals('income', $entry->type);
    }

    public function test_withdrawal_state(): void
    {
        $entry = CashRegisterEntry::factory()->withdrawal()->create();

        $this->assertEquals('withdrawal', $entry->type);
    }

    public function test_package_payment_state(): void
    {
        $entry = CashRegisterEntry::factory()->packagePayment()->create();

        $this->assertEquals('income', $entry->type);
        $this->assertEquals('Package Payment', $entry->category);
    }

    public function test_package_usage_state(): void
    {
        $entry = CashRegisterEntry::factory()->packageUsage()->create();

        $this->assertEquals('income', $entry->type);
        $this->assertEquals('package_usage', $entry->category);
        $this->assertEquals('package_credit', $entry->payment_method);
    }

    public function test_amount_cast_to_decimal(): void
    {
        $entry = CashRegisterEntry::factory()->create(['amount' => 123.45]);

        $this->assertEquals('123.45', $entry->amount);
    }
}
