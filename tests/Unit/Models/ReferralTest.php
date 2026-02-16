<?php

namespace Tests\Unit\Models;

use App\Models\Referral;
use App\Models\ReferralCode;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Basic Model Tests
    // ==========================================

    public function test_can_create_referral(): void
    {
        $referrer = User::factory()->create();
        $referred = User::factory()->create();
        $code = ReferralCode::factory()->create(['user_id' => $referrer->id]);

        $referral = Referral::create([
            'referrer_id' => $referrer->id,
            'referred_user_id' => $referred->id,
            'referral_code_id' => $code->id,
            'status' => 'pending',
            'joined_at' => now(),
        ]);

        $this->assertDatabaseHas('referrals', [
            'referrer_id' => $referrer->id,
            'referred_user_id' => $referred->id,
            'status' => 'pending',
        ]);
    }

    // ==========================================
    // Casts Tests
    // ==========================================

    public function test_joined_at_is_datetime(): void
    {
        $referral = Referral::factory()->create([
            'joined_at' => '2026-01-20 10:00:00',
        ]);

        $this->assertInstanceOf(Carbon::class, $referral->joined_at);
    }

    // ==========================================
    // Relationship Tests
    // ==========================================

    public function test_belongs_to_referrer(): void
    {
        $referrer = User::factory()->create(['name' => 'John Referrer']);
        $referral = Referral::factory()->create(['referrer_id' => $referrer->id]);

        $this->assertEquals('John Referrer', $referral->referrer->name);
    }

    public function test_belongs_to_referred_user(): void
    {
        $referred = User::factory()->create(['name' => 'Jane Referred']);
        $referral = Referral::factory()->create(['referred_user_id' => $referred->id]);

        $this->assertEquals('Jane Referred', $referral->referredUser->name);
    }

    public function test_belongs_to_referral_code(): void
    {
        $referrer = User::factory()->create();
        $code = ReferralCode::factory()->create([
            'user_id' => $referrer->id,
            'code' => 'TESTCODE123',
        ]);
        $referral = Referral::factory()->create([
            'referrer_id' => $referrer->id,
            'referral_code_id' => $code->id,
        ]);

        $this->assertEquals('TESTCODE123', $referral->referralCode->code);
    }

    // ==========================================
    // Status Tests (matching migration enum values)
    // ==========================================

    public function test_pending_status(): void
    {
        $referral = Referral::factory()->pending()->create();

        $this->assertEquals('pending', $referral->status);
    }

    public function test_confirmed_status(): void
    {
        $referral = Referral::factory()->confirmed()->create();

        $this->assertEquals('confirmed', $referral->status);
    }

    public function test_rewarded_status(): void
    {
        $referral = Referral::factory()->rewarded()->create();

        $this->assertEquals('rewarded', $referral->status);
    }

    // ==========================================
    // Relationship Integrity Tests
    // ==========================================

    public function test_referrer_and_referred_are_different_users(): void
    {
        $referrer = User::factory()->create();
        $referred = User::factory()->create();

        $referral = Referral::factory()->create([
            'referrer_id' => $referrer->id,
            'referred_user_id' => $referred->id,
        ]);

        $this->assertNotEquals($referral->referrer_id, $referral->referred_user_id);
    }
}
