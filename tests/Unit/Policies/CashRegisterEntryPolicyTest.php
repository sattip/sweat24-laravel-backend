<?php

namespace Tests\Unit\Policies;

use App\Models\CashRegisterEntry;
use App\Models\User;
use App\Policies\CashRegisterEntryPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashRegisterEntryPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected CashRegisterEntryPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new CashRegisterEntryPolicy();
    }

    // ========== viewAny Tests ==========

    public function test_admin_can_view_any_entries(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertTrue($this->policy->viewAny($admin));
    }

    public function test_trainer_can_view_any_entries(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);

        $this->assertTrue($this->policy->viewAny($trainer));
    }

    public function test_member_cannot_view_any_entries(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $this->assertFalse($this->policy->viewAny($member));
    }

    // ========== view Tests ==========

    public function test_admin_can_view_any_entry(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trainer = User::factory()->create(['role' => 'trainer']);

        $entry = CashRegisterEntry::factory()->create(['user_id' => $trainer->id]);

        $this->assertTrue($this->policy->view($admin, $entry));
    }

    public function test_trainer_can_view_own_entry(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        // Note: The policy checks 'created_by' but we need to mock it
        // For now, we test the logic with user_id since that's what the factory uses
        $entry = CashRegisterEntry::factory()->create(['user_id' => $trainer->id]);
        // Manually set created_by for the test (it's virtual/computed)
        $entry->created_by = $trainer->id;

        $this->assertTrue($this->policy->view($trainer, $entry));
    }

    public function test_trainer_cannot_view_other_trainer_entry(): void
    {
        $trainer1 = User::factory()->create(['role' => 'trainer']);
        $trainer2 = User::factory()->create(['role' => 'trainer']);

        $entry = CashRegisterEntry::factory()->create(['user_id' => $trainer1->id]);
        $entry->created_by = $trainer1->id;

        $this->assertFalse($this->policy->view($trainer2, $entry));
    }

    public function test_member_cannot_view_entry(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $admin = User::factory()->create(['role' => 'admin']);

        $entry = CashRegisterEntry::factory()->create(['user_id' => $admin->id]);
        $entry->created_by = $admin->id;

        $this->assertFalse($this->policy->view($member, $entry));
    }

    // ========== create Tests ==========

    public function test_admin_can_create_entry(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertTrue($this->policy->create($admin));
    }

    public function test_trainer_can_create_entry(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);

        $this->assertTrue($this->policy->create($trainer));
    }

    public function test_member_cannot_create_entry(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $this->assertFalse($this->policy->create($member));
    }

    // ========== update Tests ==========

    public function test_admin_can_update_entry(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $entry = CashRegisterEntry::factory()->create();

        $this->assertTrue($this->policy->update($admin, $entry));
    }

    public function test_trainer_cannot_update_entry(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        $entry = CashRegisterEntry::factory()->create(['user_id' => $trainer->id]);

        $this->assertFalse($this->policy->update($trainer, $entry));
    }

    public function test_member_cannot_update_entry(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $entry = CashRegisterEntry::factory()->create();

        $this->assertFalse($this->policy->update($member, $entry));
    }

    // ========== delete Tests ==========

    public function test_admin_can_delete_entry(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $entry = CashRegisterEntry::factory()->create();

        $this->assertTrue($this->policy->delete($admin, $entry));
    }

    public function test_trainer_cannot_delete_entry(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        $entry = CashRegisterEntry::factory()->create(['user_id' => $trainer->id]);

        $this->assertFalse($this->policy->delete($trainer, $entry));
    }

    public function test_member_cannot_delete_entry(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $entry = CashRegisterEntry::factory()->create();

        $this->assertFalse($this->policy->delete($member, $entry));
    }

    // ========== viewReports Tests ==========

    public function test_admin_can_view_reports(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertTrue($this->policy->viewReports($admin));
    }

    public function test_trainer_cannot_view_reports(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);

        $this->assertFalse($this->policy->viewReports($trainer));
    }

    public function test_member_cannot_view_reports(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $this->assertFalse($this->policy->viewReports($member));
    }

    // ========== export Tests ==========

    public function test_admin_can_export_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertTrue($this->policy->export($admin));
    }

    public function test_trainer_cannot_export_data(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);

        $this->assertFalse($this->policy->export($trainer));
    }

    public function test_member_cannot_export_data(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $this->assertFalse($this->policy->export($member));
    }

    // ========== manageSessions Tests ==========

    public function test_admin_can_manage_sessions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertTrue($this->policy->manageSessions($admin));
    }

    public function test_trainer_can_manage_sessions(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);

        $this->assertTrue($this->policy->manageSessions($trainer));
    }

    public function test_member_cannot_manage_sessions(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $this->assertFalse($this->policy->manageSessions($member));
    }

    // ========== reconcile Tests ==========

    public function test_admin_can_reconcile(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertTrue($this->policy->reconcile($admin));
    }

    public function test_trainer_cannot_reconcile(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);

        $this->assertFalse($this->policy->reconcile($trainer));
    }

    public function test_member_cannot_reconcile(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $this->assertFalse($this->policy->reconcile($member));
    }

    // ========== void Tests ==========

    public function test_admin_can_void_entry(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $entry = CashRegisterEntry::factory()->create();

        $this->assertTrue($this->policy->void($admin, $entry));
    }

    public function test_trainer_cannot_void_entry(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        $entry = CashRegisterEntry::factory()->create(['user_id' => $trainer->id]);

        $this->assertFalse($this->policy->void($trainer, $entry));
    }

    public function test_member_cannot_void_entry(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $entry = CashRegisterEntry::factory()->create();

        $this->assertFalse($this->policy->void($member, $entry));
    }
}
