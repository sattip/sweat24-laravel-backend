<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Admin Routes Protection Test
 *
 * Verifies that admin routes require proper authentication and authorization.
 */
class AdminRoutesProtectionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_points_rewards_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/admin/points/rewards');

        $response->assertStatus(401);
    }

    /** @test */
    public function admin_points_rewards_requires_admin_role(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        Sanctum::actingAs($member);

        $response = $this->getJson('/api/v1/admin/points/rewards');

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_points_rewards_accessible_by_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/admin/points/rewards');

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_statistics_dashboard_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/admin/statistics/dashboard');

        $response->assertStatus(401);
    }

    /** @test */
    public function admin_statistics_dashboard_requires_admin_role(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        Sanctum::actingAs($trainer);

        $response = $this->getJson('/api/v1/admin/statistics/dashboard');

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_financial_reports_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/admin/financial-reports/dashboard');

        $response->assertStatus(401);
    }

    /** @test */
    public function admin_financial_reports_requires_admin_role(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        Sanctum::actingAs($member);

        $response = $this->getJson('/api/v1/admin/financial-reports/dashboard');

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_payroll_agreements_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/admin/payroll-agreements');

        $response->assertStatus(401);
    }

    /** @test */
    public function admin_work_sessions_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/admin/work-sessions');

        $response->assertStatus(401);
    }

    /** @test */
    public function admin_loyalty_rewards_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/admin/loyalty-rewards');

        $response->assertStatus(401);
    }
}
