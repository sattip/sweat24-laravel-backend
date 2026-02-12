<?php

namespace Tests\Unit\Models;

use App\Models\CashRegisterEntry;
use App\Models\CashRegisterSession;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashRegisterSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_cash_register_session(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create();

        $session = CashRegisterSession::factory()->create([
            'store_id' => $store->id,
            'opened_by' => $user->id,
        ]);

        $this->assertDatabaseHas('cash_register_sessions', [
            'id' => $session->id,
            'store_id' => $store->id,
            'opened_by' => $user->id,
        ]);
    }

    public function test_session_belongs_to_store(): void
    {
        $store = Store::factory()->create();
        $session = CashRegisterSession::factory()->create(['store_id' => $store->id]);

        $this->assertInstanceOf(Store::class, $session->store);
        $this->assertEquals($store->id, $session->store->id);
    }

    public function test_session_belongs_to_opened_by_user(): void
    {
        $user = User::factory()->create();
        $session = CashRegisterSession::factory()->create(['opened_by' => $user->id]);

        $this->assertInstanceOf(User::class, $session->openedByUser);
        $this->assertEquals($user->id, $session->openedByUser->id);
    }

    public function test_session_belongs_to_closed_by_user(): void
    {
        $user = User::factory()->create();
        $session = CashRegisterSession::factory()->closed()->create(['closed_by' => $user->id]);

        $this->assertInstanceOf(User::class, $session->closedByUser);
        $this->assertEquals($user->id, $session->closedByUser->id);
    }

    public function test_open_session_is_open(): void
    {
        $session = CashRegisterSession::factory()->open()->create();

        $this->assertTrue($session->isOpen());
    }

    public function test_closed_session_is_not_open(): void
    {
        $session = CashRegisterSession::factory()->closed()->create();

        $this->assertFalse($session->isOpen());
    }

    public function test_open_state(): void
    {
        $session = CashRegisterSession::factory()->open()->create();

        $this->assertEquals('open', $session->status);
        $this->assertNull($session->closed_at);
        $this->assertNull($session->closed_by);
    }

    public function test_closed_state(): void
    {
        $session = CashRegisterSession::factory()->closed()->create();

        $this->assertEquals('closed', $session->status);
        $this->assertNotNull($session->closed_at);
        $this->assertNotNull($session->closed_by);
    }

    public function test_with_discrepancy_state(): void
    {
        $session = CashRegisterSession::factory()->withDiscrepancy()->create();

        $this->assertEquals('closed', $session->status);
        $this->assertNotEquals(0, $session->discrepancy);
    }

    public function test_calculate_expected_closing(): void
    {
        $store = Store::factory()->create();
        $session = CashRegisterSession::factory()->open()->create([
            'store_id' => $store->id,
            'opening_amount' => 100.00,
            'opened_at' => now()->subHours(1),
        ]);

        // Add income entry
        CashRegisterEntry::factory()->income()->create([
            'store_id' => $store->id,
            'amount' => 50.00,
        ]);

        // Add withdrawal entry
        CashRegisterEntry::factory()->withdrawal()->create([
            'store_id' => $store->id,
            'amount' => 20.00,
        ]);

        $expected = $session->calculateExpectedClosing();

        // 100 (opening) + 50 (income) - 20 (withdrawal) = 130
        $this->assertEquals(130.00, $expected);
    }

    public function test_decimal_casts(): void
    {
        $session = CashRegisterSession::factory()->closed()->create([
            'opening_amount' => 100.50,
            'expected_closing_amount' => 200.75,
            'actual_closing_amount' => 200.00,
            'discrepancy' => -0.75,
        ]);

        $this->assertEquals('100.50', $session->opening_amount);
        $this->assertEquals('200.75', $session->expected_closing_amount);
        $this->assertEquals('200.00', $session->actual_closing_amount);
        $this->assertEquals('-0.75', $session->discrepancy);
    }

    public function test_datetime_casts(): void
    {
        $session = CashRegisterSession::factory()->closed()->create();

        $this->assertInstanceOf(\Carbon\Carbon::class, $session->opened_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $session->closed_at);
    }
}
