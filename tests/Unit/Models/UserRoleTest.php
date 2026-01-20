<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    // ========== isAdmin() TESTS ==========

    public function test_is_admin_returns_true_for_admin_role(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertTrue($user->isAdmin());
    }

    public function test_is_admin_returns_false_for_trainer_role(): void
    {
        $user = User::factory()->trainer()->create();

        $this->assertFalse($user->isAdmin());
    }

    public function test_is_admin_returns_false_for_member_role(): void
    {
        $user = User::factory()->member()->create();

        $this->assertFalse($user->isAdmin());
    }

    // ========== isTrainer() TESTS ==========

    public function test_is_trainer_returns_true_for_trainer_role(): void
    {
        $user = User::factory()->trainer()->create();

        $this->assertTrue($user->isTrainer());
    }

    public function test_is_trainer_returns_false_for_admin_role(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertFalse($user->isTrainer());
    }

    public function test_is_trainer_returns_false_for_member_role(): void
    {
        $user = User::factory()->member()->create();

        $this->assertFalse($user->isTrainer());
    }

    // ========== isMember() TESTS ==========

    public function test_is_member_returns_true_for_member_role(): void
    {
        $user = User::factory()->member()->create();

        $this->assertTrue($user->isMember());
    }

    public function test_is_member_returns_false_for_admin_role(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertFalse($user->isMember());
    }

    public function test_is_member_returns_false_for_trainer_role(): void
    {
        $user = User::factory()->trainer()->create();

        $this->assertFalse($user->isMember());
    }

    // ========== canAccessFinancials() TESTS ==========

    public function test_can_access_financials_returns_true_for_admin(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertTrue($user->canAccessFinancials());
    }

    public function test_can_access_financials_returns_false_for_trainer(): void
    {
        $user = User::factory()->trainer()->create();

        $this->assertFalse($user->canAccessFinancials());
    }

    public function test_can_access_financials_returns_false_for_member(): void
    {
        $user = User::factory()->member()->create();

        $this->assertFalse($user->canAccessFinancials());
    }

    // ========== canAccessLimitedFinancials() TESTS ==========

    public function test_can_access_limited_financials_returns_true_for_admin(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertTrue($user->canAccessLimitedFinancials());
    }

    public function test_can_access_limited_financials_returns_true_for_trainer(): void
    {
        $user = User::factory()->trainer()->create();

        $this->assertTrue($user->canAccessLimitedFinancials());
    }

    public function test_can_access_limited_financials_returns_false_for_member(): void
    {
        $user = User::factory()->member()->create();

        $this->assertFalse($user->canAccessLimitedFinancials());
    }

    // ========== ROLE ATTRIBUTE TESTS ==========

    public function test_user_role_is_set_correctly_on_creation(): void
    {
        $admin = User::factory()->admin()->create();
        $trainer = User::factory()->trainer()->create();
        $member = User::factory()->member()->create();

        $this->assertEquals('admin', $admin->role);
        $this->assertEquals('trainer', $trainer->role);
        $this->assertEquals('member', $member->role);
    }

    public function test_default_role_is_member(): void
    {
        $user = User::factory()->create();

        $this->assertEquals('member', $user->role);
    }

    // ========== REGISTRATION STATUS TESTS ==========

    public function test_is_pending_approval_returns_true_for_pending_users(): void
    {
        $user = User::factory()->pendingApproval()->create();

        $this->assertTrue($user->isPendingApproval());
    }

    public function test_is_pending_approval_returns_false_for_active_users(): void
    {
        $user = User::factory()->active()->create();

        $this->assertFalse($user->isPendingApproval());
    }

    // ========== COMBINED ROLE CHECKS ==========

    public function test_only_one_role_check_returns_true(): void
    {
        $admin = User::factory()->admin()->create();
        $trainer = User::factory()->trainer()->create();
        $member = User::factory()->member()->create();

        // Admin checks
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isTrainer());
        $this->assertFalse($admin->isMember());

        // Trainer checks
        $this->assertFalse($trainer->isAdmin());
        $this->assertTrue($trainer->isTrainer());
        $this->assertFalse($trainer->isMember());

        // Member checks
        $this->assertFalse($member->isAdmin());
        $this->assertFalse($member->isTrainer());
        $this->assertTrue($member->isMember());
    }

    // ========== FINANCIAL ACCESS HIERARCHY ==========

    public function test_financial_access_hierarchy(): void
    {
        $admin = User::factory()->admin()->create();
        $trainer = User::factory()->trainer()->create();
        $member = User::factory()->member()->create();

        // Admin has full financial access
        $this->assertTrue($admin->canAccessFinancials());
        $this->assertTrue($admin->canAccessLimitedFinancials());

        // Trainer has only limited financial access
        $this->assertFalse($trainer->canAccessFinancials());
        $this->assertTrue($trainer->canAccessLimitedFinancials());

        // Member has no financial access
        $this->assertFalse($member->canAccessFinancials());
        $this->assertFalse($member->canAccessLimitedFinancials());
    }
}
