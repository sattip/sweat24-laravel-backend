<?php

namespace Tests\Unit\Models\Calculations;

use App\Models\CashRegisterEntry;
use App\Models\CashRegisterSession;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashRegisterSessionCalculationsTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Expected Closing Calculation Tests
    // ==========================================

    public function test_calculates_expected_closing_with_income(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create();

        $session = CashRegisterSession::create([
            'store_id' => $store->id,
            'opened_by' => $user->id,
            'opening_amount' => 100.00,
            'status' => 'open',
            'opened_at' => now()->subHours(8),
        ]);

        // Add income entries
        CashRegisterEntry::create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'type' => 'income',
            'amount' => 50.00,
            'description' => 'Package sale',
            'category' => 'sales',
            'created_at' => now()->subHours(6),
        ]);

        CashRegisterEntry::create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'type' => 'income',
            'amount' => 30.00,
            'description' => 'Session payment',
            'category' => 'sales',
            'created_at' => now()->subHours(4),
        ]);

        $expectedClosing = $session->calculateExpectedClosing();

        // Opening (100) + Income (50 + 30) = 180
        $this->assertEquals(180.00, $expectedClosing);
    }

    public function test_calculates_expected_closing_with_withdrawals(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create();

        $session = CashRegisterSession::create([
            'store_id' => $store->id,
            'opened_by' => $user->id,
            'opening_amount' => 200.00,
            'status' => 'open',
            'opened_at' => now()->subHours(8),
        ]);

        // Add withdrawal entries
        CashRegisterEntry::create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'type' => 'withdrawal',
            'amount' => 25.00,
            'description' => 'Supplies',
            'category' => 'expense',
            'created_at' => now()->subHours(6),
        ]);

        CashRegisterEntry::create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'type' => 'withdrawal',
            'amount' => 15.00,
            'description' => 'Utilities',
            'category' => 'expense',
            'created_at' => now()->subHours(4),
        ]);

        $expectedClosing = $session->calculateExpectedClosing();

        // Opening (200) - Withdrawals (25 + 15) = 160
        $this->assertEquals(160.00, $expectedClosing);
    }

    public function test_calculates_expected_closing_with_mixed_entries(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create();

        $session = CashRegisterSession::create([
            'store_id' => $store->id,
            'opened_by' => $user->id,
            'opening_amount' => 150.00,
            'status' => 'open',
            'opened_at' => now()->subHours(8),
        ]);

        // Income entries
        CashRegisterEntry::create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'type' => 'income',
            'amount' => 100.00,
            'description' => 'Package sale 1',
            'category' => 'sales',
            'created_at' => now()->subHours(7),
        ]);

        CashRegisterEntry::create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'type' => 'income',
            'amount' => 75.00,
            'description' => 'Package sale 2',
            'category' => 'sales',
            'created_at' => now()->subHours(5),
        ]);

        // Withdrawal entries
        CashRegisterEntry::create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'type' => 'withdrawal',
            'amount' => 30.00,
            'description' => 'Cleaning supplies',
            'category' => 'expense',
            'created_at' => now()->subHours(3),
        ]);

        CashRegisterEntry::create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'type' => 'withdrawal',
            'amount' => 20.00,
            'description' => 'Snacks',
            'category' => 'expense',
            'created_at' => now()->subHours(1),
        ]);

        $expectedClosing = $session->calculateExpectedClosing();

        // Opening (150) + Income (100 + 75) - Withdrawals (30 + 20) = 275
        $this->assertEquals(275.00, $expectedClosing);
    }

    public function test_calculates_expected_closing_with_no_entries(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create();

        $session = CashRegisterSession::create([
            'store_id' => $store->id,
            'opened_by' => $user->id,
            'opening_amount' => 100.00,
            'status' => 'open',
            'opened_at' => now()->subHours(8),
        ]);

        $expectedClosing = $session->calculateExpectedClosing();

        // Opening (100) + No entries = 100
        $this->assertEquals(100.00, $expectedClosing);
    }

    public function test_calculates_expected_closing_with_decimal_amounts(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create();

        $session = CashRegisterSession::create([
            'store_id' => $store->id,
            'opened_by' => $user->id,
            'opening_amount' => 50.50,
            'status' => 'open',
            'opened_at' => now()->subHours(8),
        ]);

        CashRegisterEntry::create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'type' => 'income',
            'amount' => 25.75,
            'description' => 'Sale',
            'category' => 'sales',
            'created_at' => now()->subHours(4),
        ]);

        CashRegisterEntry::create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'type' => 'withdrawal',
            'amount' => 10.25,
            'description' => 'Expense',
            'category' => 'expense',
            'created_at' => now()->subHours(2),
        ]);

        $expectedClosing = $session->calculateExpectedClosing();

        // 50.50 + 25.75 - 10.25 = 66.00
        $this->assertEquals(66.00, $expectedClosing);
    }

    // ==========================================
    // Session Status Tests
    // ==========================================

    public function test_is_open_when_status_is_open(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create();

        $session = CashRegisterSession::create([
            'store_id' => $store->id,
            'opened_by' => $user->id,
            'opening_amount' => 100.00,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $this->assertTrue($session->isOpen());
    }

    public function test_is_not_open_when_status_is_closed(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create();

        $session = CashRegisterSession::create([
            'store_id' => $store->id,
            'opened_by' => $user->id,
            'closed_by' => $user->id,
            'opening_amount' => 100.00,
            'expected_closing_amount' => 150.00,
            'actual_closing_amount' => 148.00,
            'discrepancy' => -2.00,
            'status' => 'closed',
            'opened_at' => now()->subHours(8),
            'closed_at' => now(),
        ]);

        $this->assertFalse($session->isOpen());
    }

    // ==========================================
    // Store-Specific Entry Tests
    // ==========================================

    public function test_only_includes_entries_from_session_store(): void
    {
        $store1 = Store::factory()->create();
        $store2 = Store::factory()->create();
        $user = User::factory()->create();

        $session = CashRegisterSession::create([
            'store_id' => $store1->id,
            'opened_by' => $user->id,
            'opening_amount' => 100.00,
            'status' => 'open',
            'opened_at' => now()->subHours(8),
        ]);

        // Entry for session store
        CashRegisterEntry::create([
            'store_id' => $store1->id,
            'user_id' => $user->id,
            'type' => 'income',
            'amount' => 50.00,
            'description' => 'Store 1 sale',
            'category' => 'sales',
            'created_at' => now()->subHours(4),
        ]);

        // Entry for different store (should not be included)
        CashRegisterEntry::create([
            'store_id' => $store2->id,
            'user_id' => $user->id,
            'type' => 'income',
            'amount' => 200.00,
            'description' => 'Store 2 sale',
            'category' => 'sales',
            'created_at' => now()->subHours(4),
        ]);

        $expectedClosing = $session->calculateExpectedClosing();

        // Only store1 entries: 100 + 50 = 150 (not 350)
        $this->assertEquals(150.00, $expectedClosing);
    }

    public function test_only_includes_entries_during_session_time(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create();

        // Use DB facade to insert entries with specific timestamps
        $sessionOpenedAt = Carbon::parse('2026-01-20 09:00:00');

        $session = CashRegisterSession::create([
            'store_id' => $store->id,
            'opened_by' => $user->id,
            'opening_amount' => 100.00,
            'status' => 'open',
            'opened_at' => $sessionOpenedAt,
        ]);

        // Insert entry before session opened using DB facade
        \DB::table('cash_register_entries')->insert([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'type' => 'income',
            'amount' => 1000.00,
            'description' => 'Before session',
            'category' => 'sales',
            'created_at' => Carbon::parse('2026-01-20 08:00:00'),
            'updated_at' => Carbon::parse('2026-01-20 08:00:00'),
        ]);

        // Insert entry during session using DB facade
        \DB::table('cash_register_entries')->insert([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'type' => 'income',
            'amount' => 75.00,
            'description' => 'During session',
            'category' => 'sales',
            'created_at' => Carbon::parse('2026-01-20 12:00:00'),
            'updated_at' => Carbon::parse('2026-01-20 12:00:00'),
        ]);

        $expectedClosing = $session->calculateExpectedClosing();

        // Only during-session entries: 100 + 75 = 175 (not 1175)
        $this->assertEquals(175.00, $expectedClosing);
    }

    // ==========================================
    // Discrepancy Scenarios
    // ==========================================

    public function test_large_income_day(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create();

        $session = CashRegisterSession::create([
            'store_id' => $store->id,
            'opened_by' => $user->id,
            'opening_amount' => 200.00,
            'status' => 'open',
            'opened_at' => now()->subHours(10),
        ]);

        // Multiple large sales
        for ($i = 0; $i < 10; $i++) {
            CashRegisterEntry::create([
                'store_id' => $store->id,
                'user_id' => $user->id,
                'type' => 'income',
                'amount' => 150.00,
                'description' => "Package sale $i",
                'category' => 'sales',
                'created_at' => now()->subHours(9 - $i),
            ]);
        }

        $expectedClosing = $session->calculateExpectedClosing();

        // 200 + (10 × 150) = 1700
        $this->assertEquals(1700.00, $expectedClosing);
    }

    public function test_more_withdrawals_than_income(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create();

        $session = CashRegisterSession::create([
            'store_id' => $store->id,
            'opened_by' => $user->id,
            'opening_amount' => 500.00,
            'status' => 'open',
            'opened_at' => now()->subHours(8),
        ]);

        CashRegisterEntry::create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'type' => 'income',
            'amount' => 100.00,
            'description' => 'Small sale',
            'category' => 'sales',
            'created_at' => now()->subHours(6),
        ]);

        CashRegisterEntry::create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'type' => 'withdrawal',
            'amount' => 400.00,
            'description' => 'Equipment purchase',
            'category' => 'expense',
            'created_at' => now()->subHours(4),
        ]);

        $expectedClosing = $session->calculateExpectedClosing();

        // 500 + 100 - 400 = 200
        $this->assertEquals(200.00, $expectedClosing);
    }

    public function test_zero_opening_amount(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create();

        $session = CashRegisterSession::create([
            'store_id' => $store->id,
            'opened_by' => $user->id,
            'opening_amount' => 0.00,
            'status' => 'open',
            'opened_at' => now()->subHours(8),
        ]);

        CashRegisterEntry::create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'type' => 'income',
            'amount' => 250.00,
            'description' => 'Day sales',
            'category' => 'sales',
            'created_at' => now()->subHours(4),
        ]);

        $expectedClosing = $session->calculateExpectedClosing();

        // 0 + 250 = 250
        $this->assertEquals(250.00, $expectedClosing);
    }

    // ==========================================
    // Multiple Sessions Per Store Tests
    // ==========================================

    public function test_multiple_sessions_same_store_different_days(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create();

        // Yesterday's closed session
        CashRegisterSession::create([
            'store_id' => $store->id,
            'opened_by' => $user->id,
            'closed_by' => $user->id,
            'opening_amount' => 100.00,
            'expected_closing_amount' => 200.00,
            'actual_closing_amount' => 200.00,
            'discrepancy' => 0.00,
            'status' => 'closed',
            'opened_at' => Carbon::parse('2026-01-19 09:00:00'),
            'closed_at' => Carbon::parse('2026-01-19 18:00:00'),
        ]);

        // Entry from yesterday (should not affect today's session) - use DB facade
        \DB::table('cash_register_entries')->insert([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'type' => 'income',
            'amount' => 100.00,
            'description' => 'Yesterday sale',
            'category' => 'sales',
            'created_at' => Carbon::parse('2026-01-19 12:00:00'),
            'updated_at' => Carbon::parse('2026-01-19 12:00:00'),
        ]);

        // Today's session
        $todaySession = CashRegisterSession::create([
            'store_id' => $store->id,
            'opened_by' => $user->id,
            'opening_amount' => 200.00,
            'status' => 'open',
            'opened_at' => Carbon::parse('2026-01-20 09:00:00'),
        ]);

        // Today's entry - use DB facade
        \DB::table('cash_register_entries')->insert([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'type' => 'income',
            'amount' => 50.00,
            'description' => 'Today sale',
            'category' => 'sales',
            'created_at' => Carbon::parse('2026-01-20 12:00:00'),
            'updated_at' => Carbon::parse('2026-01-20 12:00:00'),
        ]);

        $expectedClosing = $todaySession->calculateExpectedClosing();

        // Only today's entries: 200 + 50 = 250 (not 350)
        $this->assertEquals(250.00, $expectedClosing);
    }
}
