<?php

namespace Tests\Unit\Models;

use App\Models\PointsTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PointsTransactionTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Basic Model Tests
    // ==========================================

    public function test_can_create_points_transaction(): void
    {
        $transaction = PointsTransaction::factory()->create([
            'amount' => 100,
            'type' => 'earned',
            'source' => 'purchase',
        ]);

        $this->assertDatabaseHas('points_transactions', [
            'id' => $transaction->id,
            'amount' => 100,
            'type' => 'earned',
            'source' => 'purchase',
        ]);
    }

    // ==========================================
    // Casts Tests
    // ==========================================

    public function test_amount_is_integer(): void
    {
        $transaction = PointsTransaction::factory()->create([
            'amount' => 150,
        ]);

        $this->assertIsInt($transaction->amount);
        $this->assertEquals(150, $transaction->amount);
    }

    public function test_balance_after_is_integer(): void
    {
        $transaction = PointsTransaction::factory()->create([
            'balance_after' => 500,
        ]);

        $this->assertIsInt($transaction->balance_after);
        $this->assertEquals(500, $transaction->balance_after);
    }

    public function test_metadata_is_array(): void
    {
        $transaction = PointsTransaction::factory()->withMetadata([
            'order_id' => 123,
            'product_name' => 'Monthly Pass',
        ])->create();

        $this->assertIsArray($transaction->metadata);
        $this->assertEquals(123, $transaction->metadata['order_id']);
        $this->assertEquals('Monthly Pass', $transaction->metadata['product_name']);
    }

    // ==========================================
    // Relationship Tests
    // ==========================================

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create(['name' => 'Test User']);
        $transaction = PointsTransaction::factory()->create(['user_id' => $user->id]);

        $this->assertEquals('Test User', $transaction->user->name);
    }

    // ==========================================
    // Scope Tests
    // ==========================================

    public function test_scope_earned(): void
    {
        $user = User::factory()->create();

        PointsTransaction::factory()->earned()->count(2)->create(['user_id' => $user->id]);
        PointsTransaction::factory()->spent()->create(['user_id' => $user->id]);

        $earnedTransactions = PointsTransaction::earned()->get();

        $this->assertCount(2, $earnedTransactions);
        $this->assertTrue($earnedTransactions->every(fn ($t) => $t->type === 'earned'));
    }

    public function test_scope_spent(): void
    {
        $user = User::factory()->create();

        PointsTransaction::factory()->earned()->create(['user_id' => $user->id]);
        PointsTransaction::factory()->spent()->count(2)->create(['user_id' => $user->id]);

        $spentTransactions = PointsTransaction::spent()->get();

        $this->assertCount(2, $spentTransactions);
        $this->assertTrue($spentTransactions->every(fn ($t) => $t->type === 'spent'));
    }

    public function test_scope_current_month(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-20 12:00:00'));

        $user = User::factory()->create();

        // Create transaction this month using DB to control timestamp
        \DB::table('points_transactions')->insert([
            'user_id' => $user->id,
            'amount' => 100,
            'type' => 'earned',
            'source' => 'purchase',
            'balance_after' => 100,
            'created_at' => Carbon::parse('2026-01-15 10:00:00'),
            'updated_at' => Carbon::parse('2026-01-15 10:00:00'),
        ]);

        \DB::table('points_transactions')->insert([
            'user_id' => $user->id,
            'amount' => 50,
            'type' => 'earned',
            'source' => 'referral',
            'balance_after' => 150,
            'created_at' => Carbon::parse('2026-01-10 10:00:00'),
            'updated_at' => Carbon::parse('2026-01-10 10:00:00'),
        ]);

        // Create transaction last month
        \DB::table('points_transactions')->insert([
            'user_id' => $user->id,
            'amount' => 200,
            'type' => 'earned',
            'source' => 'bonus',
            'balance_after' => 350,
            'created_at' => Carbon::parse('2025-12-15 10:00:00'),
            'updated_at' => Carbon::parse('2025-12-15 10:00:00'),
        ]);

        $currentMonthTransactions = PointsTransaction::currentMonth()->get();

        $this->assertCount(2, $currentMonthTransactions);

        Carbon::setTestNow();
    }

    public function test_scope_by_source(): void
    {
        $user = User::factory()->create();

        PointsTransaction::factory()->fromSource('purchase')->count(2)->create(['user_id' => $user->id]);
        PointsTransaction::factory()->fromSource('referral')->create(['user_id' => $user->id]);
        PointsTransaction::factory()->fromSource('bonus')->create(['user_id' => $user->id]);

        $purchaseTransactions = PointsTransaction::bySource('purchase')->get();

        $this->assertCount(2, $purchaseTransactions);
        $this->assertTrue($purchaseTransactions->every(fn ($t) => $t->source === 'purchase'));
    }

    // ==========================================
    // Reference Tests
    // ==========================================

    public function test_can_store_reference_type_and_id(): void
    {
        $transaction = PointsTransaction::factory()->create([
            'reference_type' => 'App\\Models\\Order',
            'reference_id' => 456,
        ]);

        $this->assertEquals('App\\Models\\Order', $transaction->reference_type);
        $this->assertEquals(456, $transaction->reference_id);
    }

    // ==========================================
    // Combined Scopes Tests
    // ==========================================

    public function test_combined_scopes(): void
    {
        $user = User::factory()->create();

        PointsTransaction::factory()->earned()->fromSource('purchase')->count(2)->create(['user_id' => $user->id]);
        PointsTransaction::factory()->earned()->fromSource('referral')->create(['user_id' => $user->id]);
        PointsTransaction::factory()->spent()->fromSource('redemption')->create(['user_id' => $user->id]);

        $earnedFromPurchase = PointsTransaction::earned()->bySource('purchase')->get();

        $this->assertCount(2, $earnedFromPurchase);
    }
}
