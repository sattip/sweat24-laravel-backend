<?php

namespace Tests\Unit\Models;

use App\Models\Referral;
use App\Models\ReferralCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralCodeTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Basic Model Tests
    // ==========================================

    public function test_can_create_referral_code(): void
    {
        $user = User::factory()->create();

        $referralCode = ReferralCode::create([
            'user_id' => $user->id,
            'code' => 'TESTCODE1234',
            'link' => 'sweat24.com/join?ref=TESTCODE1234',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('referral_codes', [
            'user_id' => $user->id,
            'code' => 'TESTCODE1234',
        ]);
    }

    // ==========================================
    // Casts Tests
    // ==========================================

    public function test_is_active_is_boolean(): void
    {
        $referralCode = ReferralCode::factory()->create(['is_active' => 1]);

        $this->assertTrue($referralCode->is_active);
        $this->assertIsBool($referralCode->is_active);
    }

    // ==========================================
    // Relationship Tests
    // ==========================================

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create(['name' => 'Test User']);
        $referralCode = ReferralCode::factory()->create(['user_id' => $user->id]);

        $this->assertEquals('Test User', $referralCode->user->name);
    }

    public function test_has_many_referrals(): void
    {
        $referrer = User::factory()->create();
        $referralCode = ReferralCode::factory()->create(['user_id' => $referrer->id]);

        Referral::factory()->count(3)->create([
            'referrer_id' => $referrer->id,
            'referral_code_id' => $referralCode->id,
        ]);

        $this->assertCount(3, $referralCode->referrals);
    }

    // ==========================================
    // Auto-generated Code Tests
    // ==========================================

    public function test_code_auto_generated_on_create(): void
    {
        $user = User::factory()->create(['name' => 'John Smith']);

        $referralCode = ReferralCode::create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $this->assertNotNull($referralCode->code);
        // Code should start with letters from user's name
        $this->assertStringStartsWith('JOHN', $referralCode->code);
    }

    public function test_link_auto_generated_on_create(): void
    {
        $user = User::factory()->create(['name' => 'Jane Doe']);

        $referralCode = ReferralCode::create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $this->assertNotNull($referralCode->link);
        $this->assertStringContainsString('sweat24.com/join?ref=', $referralCode->link);
        $this->assertStringContainsString($referralCode->code, $referralCode->link);
    }

    public function test_custom_code_not_overwritten(): void
    {
        $user = User::factory()->create();

        $referralCode = ReferralCode::create([
            'user_id' => $user->id,
            'code' => 'MYCODE1234',
            'is_active' => true,
        ]);

        $this->assertEquals('MYCODE1234', $referralCode->code);
    }

    // ==========================================
    // Accessor Tests
    // ==========================================

    public function test_points_earned_accessor_calculated_from_referrals(): void
    {
        $referrer = User::factory()->create();
        $referralCode = ReferralCode::factory()->create([
            'user_id' => $referrer->id,
        ]);

        Referral::factory()->count(5)->create([
            'referrer_id' => $referrer->id,
            'referral_code_id' => $referralCode->id,
        ]);

        // 5 referrals * 10 points = 50
        $this->assertEquals(50, $referralCode->points_earned);
    }

    public function test_referred_users_count_accessor(): void
    {
        $referrer = User::factory()->create();
        $referralCode = ReferralCode::factory()->create(['user_id' => $referrer->id]);

        Referral::factory()->count(7)->create([
            'referrer_id' => $referrer->id,
            'referral_code_id' => $referralCode->id,
        ]);

        $this->assertEquals(7, $referralCode->referred_users_count);
    }

    // ==========================================
    // State Tests
    // ==========================================

    public function test_active_state(): void
    {
        $referralCode = ReferralCode::factory()->active()->create();

        $this->assertTrue($referralCode->is_active);
    }

    public function test_inactive_state(): void
    {
        $referralCode = ReferralCode::factory()->inactive()->create();

        $this->assertFalse($referralCode->is_active);
    }
}
