<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected UserPolicy $policy;
    protected User $admin;
    protected User $trainer;
    protected User $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new UserPolicy();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->trainer = User::factory()->create(['role' => 'trainer']);
        $this->member = User::factory()->create(['role' => 'member']);
    }

    /** @test */
    public function admin_can_view_any_users(): void
    {
        $this->assertTrue($this->policy->viewAny($this->admin));
    }

    /** @test */
    public function trainer_can_view_any_users(): void
    {
        $this->assertTrue($this->policy->viewAny($this->trainer));
    }

    /** @test */
    public function member_cannot_view_any_users(): void
    {
        $this->assertFalse($this->policy->viewAny($this->member));
    }

    /** @test */
    public function admin_can_view_any_user(): void
    {
        $otherUser = User::factory()->create();
        $this->assertTrue($this->policy->view($this->admin, $otherUser));
    }

    /** @test */
    public function member_can_view_own_profile(): void
    {
        $this->assertTrue($this->policy->view($this->member, $this->member));
    }

    /** @test */
    public function member_cannot_view_other_user_profile(): void
    {
        $otherMember = User::factory()->create(['role' => 'member']);
        $this->assertFalse($this->policy->view($this->member, $otherMember));
    }

    /** @test */
    public function only_admin_can_create_users(): void
    {
        $this->assertTrue($this->policy->create($this->admin));
        $this->assertFalse($this->policy->create($this->trainer));
        $this->assertFalse($this->policy->create($this->member));
    }

    /** @test */
    public function admin_can_update_any_user(): void
    {
        $otherUser = User::factory()->create();
        $this->assertTrue($this->policy->update($this->admin, $otherUser));
    }

    /** @test */
    public function user_can_update_own_profile(): void
    {
        $this->assertTrue($this->policy->update($this->member, $this->member));
    }

    /** @test */
    public function member_cannot_update_other_user(): void
    {
        $otherMember = User::factory()->create(['role' => 'member']);
        $this->assertFalse($this->policy->update($this->member, $otherMember));
    }

    /** @test */
    public function admin_can_delete_other_user(): void
    {
        $otherUser = User::factory()->create();
        $this->assertTrue($this->policy->delete($this->admin, $otherUser));
    }

    /** @test */
    public function admin_cannot_delete_self(): void
    {
        $this->assertFalse($this->policy->delete($this->admin, $this->admin));
    }

    /** @test */
    public function trainer_cannot_delete_users(): void
    {
        $otherUser = User::factory()->create();
        $this->assertFalse($this->policy->delete($this->trainer, $otherUser));
    }

    /** @test */
    public function only_admin_can_approve_users(): void
    {
        $pendingUser = User::factory()->create(['status' => 'pending_approval']);
        $this->assertTrue($this->policy->approve($this->admin, $pendingUser));
        $this->assertFalse($this->policy->approve($this->trainer, $pendingUser));
        $this->assertFalse($this->policy->approve($this->member, $pendingUser));
    }

    /** @test */
    public function only_admin_can_reject_users(): void
    {
        $pendingUser = User::factory()->create(['status' => 'pending_approval']);
        $this->assertTrue($this->policy->reject($this->admin, $pendingUser));
        $this->assertFalse($this->policy->reject($this->trainer, $pendingUser));
        $this->assertFalse($this->policy->reject($this->member, $pendingUser));
    }

    /** @test */
    public function admin_can_change_other_user_role(): void
    {
        $otherUser = User::factory()->create();
        $this->assertTrue($this->policy->changeRole($this->admin, $otherUser));
    }

    /** @test */
    public function admin_cannot_change_own_role(): void
    {
        $this->assertFalse($this->policy->changeRole($this->admin, $this->admin));
    }

    /** @test */
    public function trainer_cannot_change_roles(): void
    {
        $otherUser = User::factory()->create();
        $this->assertFalse($this->policy->changeRole($this->trainer, $otherUser));
    }
}
