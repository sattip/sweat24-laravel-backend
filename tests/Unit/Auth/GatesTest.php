<?php

namespace Tests\Unit\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class GatesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $trainer;
    protected User $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->trainer = User::factory()->create(['role' => 'trainer']);
        $this->member = User::factory()->create(['role' => 'member']);
    }

    /** @test */
    public function admin_gate_allows_only_admins(): void
    {
        $this->assertTrue(Gate::forUser($this->admin)->allows('admin'));
        $this->assertFalse(Gate::forUser($this->trainer)->allows('admin'));
        $this->assertFalse(Gate::forUser($this->member)->allows('admin'));
    }

    /** @test */
    public function trainer_gate_allows_admins_and_trainers(): void
    {
        $this->assertTrue(Gate::forUser($this->admin)->allows('trainer'));
        $this->assertTrue(Gate::forUser($this->trainer)->allows('trainer'));
        $this->assertFalse(Gate::forUser($this->member)->allows('trainer'));
    }

    /** @test */
    public function manage_users_gate_allows_only_admins(): void
    {
        $this->assertTrue(Gate::forUser($this->admin)->allows('manage-users'));
        $this->assertFalse(Gate::forUser($this->trainer)->allows('manage-users'));
        $this->assertFalse(Gate::forUser($this->member)->allows('manage-users'));
    }

    /** @test */
    public function access_financials_gate_allows_only_admins(): void
    {
        $this->assertTrue(Gate::forUser($this->admin)->allows('access-financials'));
        $this->assertFalse(Gate::forUser($this->trainer)->allows('access-financials'));
        $this->assertFalse(Gate::forUser($this->member)->allows('access-financials'));
    }

    /** @test */
    public function access_limited_financials_gate_allows_admins_and_trainers(): void
    {
        $this->assertTrue(Gate::forUser($this->admin)->allows('access-limited-financials'));
        $this->assertTrue(Gate::forUser($this->trainer)->allows('access-limited-financials'));
        $this->assertFalse(Gate::forUser($this->member)->allows('access-limited-financials'));
    }

    /** @test */
    public function manage_classes_gate_allows_admins_and_trainers(): void
    {
        $this->assertTrue(Gate::forUser($this->admin)->allows('manage-classes'));
        $this->assertTrue(Gate::forUser($this->trainer)->allows('manage-classes'));
        $this->assertFalse(Gate::forUser($this->member)->allows('manage-classes'));
    }

    /** @test */
    public function manage_bookings_gate_allows_admins_and_trainers(): void
    {
        $this->assertTrue(Gate::forUser($this->admin)->allows('manage-bookings'));
        $this->assertTrue(Gate::forUser($this->trainer)->allows('manage-bookings'));
        $this->assertFalse(Gate::forUser($this->member)->allows('manage-bookings'));
    }

    /** @test */
    public function manage_packages_gate_allows_only_admins(): void
    {
        $this->assertTrue(Gate::forUser($this->admin)->allows('manage-packages'));
        $this->assertFalse(Gate::forUser($this->trainer)->allows('manage-packages'));
        $this->assertFalse(Gate::forUser($this->member)->allows('manage-packages'));
    }

    /** @test */
    public function view_reports_gate_allows_only_admins(): void
    {
        $this->assertTrue(Gate::forUser($this->admin)->allows('view-reports'));
        $this->assertFalse(Gate::forUser($this->trainer)->allows('view-reports'));
        $this->assertFalse(Gate::forUser($this->member)->allows('view-reports'));
    }

    /** @test */
    public function manage_settings_gate_allows_only_admins(): void
    {
        $this->assertTrue(Gate::forUser($this->admin)->allows('manage-settings'));
        $this->assertFalse(Gate::forUser($this->trainer)->allows('manage-settings'));
        $this->assertFalse(Gate::forUser($this->member)->allows('manage-settings'));
    }

    /** @test */
    public function access_cash_register_gate_allows_admins_and_trainers(): void
    {
        $this->assertTrue(Gate::forUser($this->admin)->allows('access-cash-register'));
        $this->assertTrue(Gate::forUser($this->trainer)->allows('access-cash-register'));
        $this->assertFalse(Gate::forUser($this->member)->allows('access-cash-register'));
    }

    /** @test */
    public function approve_users_gate_allows_only_admins(): void
    {
        $this->assertTrue(Gate::forUser($this->admin)->allows('approve-users'));
        $this->assertFalse(Gate::forUser($this->trainer)->allows('approve-users'));
        $this->assertFalse(Gate::forUser($this->member)->allows('approve-users'));
    }
}
