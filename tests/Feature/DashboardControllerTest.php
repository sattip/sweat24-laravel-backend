<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\CashRegisterEntry;
use App\Models\GymClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    // ========== Dashboard Stats Tests ==========
    // Note: The dashboard/stats endpoint requires authentication and returns role-based stats

    public function test_dashboard_stats_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/dashboard/stats');

        $response->assertStatus(401);
    }

    public function test_authenticated_stats_returns_role_based_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/dashboard/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_members',
                'active_members',
                'monthly_revenue',
                'pending_payments',
                'overdue_payments',
            ]);
    }

    public function test_admin_stats_includes_dormant_and_inactive_members(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/dashboard/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'dormant_members',
                'inactive_customers',
            ]);
    }

    public function test_trainer_stats_includes_trainer_specific_data(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        Sanctum::actingAs($trainer);

        $response = $this->getJson('/api/v1/dashboard/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'my_booking_requests',
                'customers_to_renew',
                'today_tasks',
                'unread_messages',
            ]);
    }

    public function test_stats_counts_total_and_active_members(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        Sanctum::actingAs($admin);

        User::factory()->count(3)->create(['status' => 'active']);
        User::factory()->count(2)->create(['status' => 'inactive']);

        $response = $this->getJson('/api/v1/dashboard/stats');

        // 3 active + 1 admin = 4 active members, total = 6
        $response->assertStatus(200)
            ->assertJsonPath('total_members', 6)
            ->assertJsonPath('active_members', 4);
    }

    public function test_stats_counts_monthly_revenue(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        // Create income entries for this month
        CashRegisterEntry::factory()->count(2)->create([
            'type' => 'income',
            'amount' => 100.00,
            'created_at' => now(),
        ]);

        // Create withdrawal entries (should not be counted as income)
        CashRegisterEntry::factory()->create([
            'type' => 'withdrawal',
            'amount' => 50.00,
            'created_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/dashboard/stats');

        $response->assertStatus(200);

        // Monthly revenue is returned as a numeric value
        $data = $response->json();
        $this->assertEquals(200.00, (float) $data['monthly_revenue']);
    }

    // ========== Activities Tests ==========

    public function test_activities_returns_correct_structure(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/dashboard/activities');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'activities',
                'total_count',
            ]);
    }

    public function test_activities_returns_activity_logs_with_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        ActivityLog::create([
            'user_id' => $admin->id,
            'activity_type' => 'login',
            'action' => 'User logged in',
            'properties' => ['ip' => '127.0.0.1'],
        ]);

        $response = $this->getJson('/api/v1/dashboard/activities');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'activities')
            ->assertJsonPath('activities.0.activity_type', 'login')
            ->assertJsonPath('activities.0.user.id', $admin->id);
    }

    public function test_activities_limits_to_50_records(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        // Create 60 activity logs
        for ($i = 0; $i < 60; $i++) {
            ActivityLog::create([
                'user_id' => $admin->id,
                'activity_type' => 'test',
                'action' => "Action $i",
            ]);
        }

        $response = $this->getJson('/api/v1/dashboard/activities');

        $response->assertStatus(200)
            ->assertJsonCount(50, 'activities')
            ->assertJsonPath('total_count', 60);
    }

    public function test_activities_ordered_by_most_recent(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $first = ActivityLog::create([
            'user_id' => $admin->id,
            'activity_type' => 'first',
            'action' => 'First action',
        ]);
        // Set created_at directly via query to bypass mass assignment
        ActivityLog::where('id', $first->id)->update(['created_at' => now()->subHour()]);

        ActivityLog::create([
            'user_id' => $admin->id,
            'activity_type' => 'second',
            'action' => 'Second action',
        ]);

        $response = $this->getJson('/api/v1/dashboard/activities');

        $response->assertStatus(200)
            ->assertJsonPath('activities.0.activity_type', 'second')
            ->assertJsonPath('activities.1.activity_type', 'first');
    }

    public function test_activities_handles_deleted_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        // Create a user, add an activity log, then delete the user
        $user = User::factory()->create();
        $userId = $user->id;

        ActivityLog::create([
            'user_id' => $userId,
            'activity_type' => 'test',
            'action' => 'Test action',
        ]);

        // Force delete to bypass FK constraints for this test
        $user->forceDelete();

        $response = $this->getJson('/api/v1/dashboard/activities');

        $response->assertStatus(200)
            ->assertJsonPath('activities.0.user', null);
    }
}
