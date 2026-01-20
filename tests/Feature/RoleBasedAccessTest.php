<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\GymClass;
use App\Models\Package;
use App\Models\Booking;
use App\Models\UserPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoleBasedAccessTest extends TestCase
{
    use RefreshDatabase;

    // ========== ADMIN-ONLY ENDPOINTS ==========

    public function test_admin_can_access_analytics_dashboard(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/admin/analytics/dashboard');

        $response->assertStatus(200);
    }

    public function test_trainer_cannot_access_analytics_dashboard(): void
    {
        $trainer = User::factory()->trainer()->create();
        Sanctum::actingAs($trainer);

        $response = $this->getJson('/api/v1/admin/analytics/dashboard');

        $response->assertStatus(403);
    }

    public function test_member_cannot_access_analytics_dashboard(): void
    {
        $member = User::factory()->member()->create();
        Sanctum::actingAs($member);

        $response = $this->getJson('/api/v1/admin/analytics/dashboard');

        $response->assertStatus(403);
    }

    public function test_admin_can_approve_user(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $pendingUser = User::factory()->pendingApproval()->create();

        $response = $this->postJson("/api/v1/admin/users/{$pendingUser->id}/approve");

        $this->assertNotEquals(401, $response->status());
        $this->assertNotEquals(403, $response->status());
    }

    public function test_trainer_cannot_approve_user_via_v1_admin(): void
    {
        $trainer = User::factory()->trainer()->create();
        Sanctum::actingAs($trainer);

        $pendingUser = User::factory()->pendingApproval()->create();

        $response = $this->postJson("/api/v1/admin/users/{$pendingUser->id}/approve");

        $response->assertStatus(403);
    }

    public function test_member_cannot_approve_user(): void
    {
        $member = User::factory()->member()->create();
        Sanctum::actingAs($member);

        $pendingUser = User::factory()->pendingApproval()->create();

        $response = $this->postJson("/api/v1/admin/users/{$pendingUser->id}/approve");

        $response->assertStatus(403);
    }

    public function test_admin_can_access_recurring_class_info(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $gymClass = GymClass::factory()->create();

        $response = $this->getJson("/api/v1/admin/classes/{$gymClass->id}/recurring/info");

        // Should not be 403 forbidden (access control passes)
        $this->assertNotEquals(403, $response->status());
    }

    public function test_trainer_cannot_access_recurring_class_info(): void
    {
        $trainer = User::factory()->trainer()->create();
        Sanctum::actingAs($trainer);

        $gymClass = GymClass::factory()->create();

        $response = $this->getJson("/api/v1/admin/classes/{$gymClass->id}/recurring/info");

        $response->assertStatus(403);
    }

    // ========== ADMIN + TRAINER ENDPOINTS ==========

    public function test_admin_can_create_package(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/packages', [
            'name' => 'Admin Package',
            'type' => 'sessions',
            'duration' => 30,
            'description' => 'Package created by admin',
            'price' => 150.00,
            'credits' => 15,
            'active' => true,
        ]);

        $this->assertNotEquals(403, $response->status());
        $this->assertNotEquals(401, $response->status());
    }

    public function test_trainer_can_create_package(): void
    {
        $trainer = User::factory()->trainer()->create();
        Sanctum::actingAs($trainer);

        $response = $this->postJson('/api/v1/packages', [
            'name' => 'Trainer Package',
            'type' => 'sessions',
            'duration' => 30,
            'description' => 'Package created by trainer',
            'price' => 100.00,
            'credits' => 10,
            'active' => true,
        ]);

        $this->assertNotEquals(403, $response->status());
        $this->assertNotEquals(401, $response->status());
    }

    public function test_member_cannot_create_package(): void
    {
        $member = User::factory()->member()->create();
        Sanctum::actingAs($member);

        $response = $this->postJson('/api/v1/packages', [
            'name' => 'Member Package',
            'type' => 'sessions',
            'duration' => 30,
            'description' => 'Package created by member',
            'price' => 100.00,
            'credits' => 10,
            'active' => true,
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_access_class_waitlist(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

                $gymClass = GymClass::factory()->create();

        $response = $this->getJson("/api/v1/classes/{$gymClass->id}/waitlist");

        $this->assertNotEquals(403, $response->status());
    }

    public function test_trainer_can_access_class_waitlist(): void
    {
        $trainer = User::factory()->trainer()->create();
        Sanctum::actingAs($trainer);

                $gymClass = GymClass::factory()->create();

        $response = $this->getJson("/api/v1/classes/{$gymClass->id}/waitlist");

        $this->assertNotEquals(403, $response->status());
    }

    public function test_member_cannot_access_class_waitlist(): void
    {
        $member = User::factory()->member()->create();
        Sanctum::actingAs($member);

                $gymClass = GymClass::factory()->create();

        $response = $this->getJson("/api/v1/classes/{$gymClass->id}/waitlist");

        $response->assertStatus(403);
    }

    public function test_admin_can_create_fitness_class(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/fitness-classes', [
            'name' => 'Admin Fitness Class',
            'description' => 'Test class',
            'duration' => 60,
            'capacity' => 20,
            'type' => 'group',
        ]);

        $this->assertNotEquals(403, $response->status());
        $this->assertNotEquals(401, $response->status());
    }

    public function test_trainer_can_create_fitness_class(): void
    {
        $trainer = User::factory()->trainer()->create();
        Sanctum::actingAs($trainer);

        $response = $this->postJson('/api/v1/fitness-classes', [
            'name' => 'Trainer Fitness Class',
            'description' => 'Test class',
            'duration' => 60,
            'capacity' => 20,
            'type' => 'group',
        ]);

        $this->assertNotEquals(403, $response->status());
        $this->assertNotEquals(401, $response->status());
    }

    public function test_member_cannot_create_fitness_class(): void
    {
        $member = User::factory()->member()->create();
        Sanctum::actingAs($member);

        $response = $this->postJson('/api/v1/fitness-classes', [
            'name' => 'Member Fitness Class',
            'description' => 'Test class',
            'duration' => 60,
            'capacity' => 20,
            'type' => 'group',
        ]);

        $response->assertStatus(403);
    }

    // ========== MEMBER ENDPOINTS ==========

    public function test_member_can_access_own_profile(): void
    {
        $member = User::factory()->member()->create();
        Sanctum::actingAs($member);

        $response = $this->getJson('/api/v1/profile');

        $response->assertStatus(200);
    }

    public function test_member_can_access_booking_history(): void
    {
        $member = User::factory()->member()->create();
        Sanctum::actingAs($member);

        $response = $this->getJson('/api/v1/profile/booking-history');

        $response->assertStatus(200);
    }

    public function test_member_can_access_own_packages(): void
    {
        $member = User::factory()->member()->create();
        Sanctum::actingAs($member);

        $response = $this->getJson('/api/v1/my-active-packages');

        $response->assertStatus(200);
    }

    public function test_member_can_join_waitlist(): void
    {
        $member = User::factory()->member()->create();
        Sanctum::actingAs($member);

                $gymClass = GymClass::factory()->create([
            'max_participants' => 0, // Full class to test waitlist
        ]);

        $response = $this->postJson("/api/v1/classes/{$gymClass->id}/waitlist/join");

        // Should not be 401 or 403
        $this->assertNotEquals(401, $response->status());
        $this->assertNotEquals(403, $response->status());
    }

    public function test_member_can_access_referral_data(): void
    {
        $member = User::factory()->member()->create();
        Sanctum::actingAs($member);

        $response = $this->getJson('/api/v1/referral/data');

        $response->assertStatus(200);
    }

    // ========== PUBLIC ENDPOINTS (NO AUTH REQUIRED) ==========

    public function test_public_can_access_classes_list(): void
    {
        // No authentication
        $response = $this->getJson('/api/v1/classes');

        $response->assertStatus(200);
    }

    public function test_public_can_access_packages_list(): void
    {
        // No authentication
        $response = $this->getJson('/api/v1/packages');

        $response->assertStatus(200);
    }

    public function test_public_can_access_trainers_list(): void
    {
        // No authentication
        $response = $this->getJson('/api/v1/trainers');

        $response->assertStatus(200);
    }

    // ========== BOOKING OPERATIONS ==========

    public function test_member_can_create_booking(): void
    {
        $member = User::factory()->member()->create();

                $gymClass = GymClass::factory()->create([
            'max_participants' => 10,
            'date' => now()->addDays(1)->format('Y-m-d'),
            'time' => '10:00:00',
        ]);

        // Public booking endpoint doesn't require auth
        $response = $this->postJson('/api/v1/bookings', [
            'class_id' => $gymClass->id,
            'user_id' => $member->id,
        ]);

        // Booking creation should work (may need specific data)
        $this->assertNotEquals(401, $response->status());
        $this->assertNotEquals(403, $response->status());
    }

    // ========== FINANCIAL ROUTES ==========

    public function test_admin_can_access_cash_register(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/cash-register');

        $response->assertStatus(200);
    }

    public function test_trainer_can_access_cash_register(): void
    {
        $trainer = User::factory()->trainer()->create();
        Sanctum::actingAs($trainer);

        $response = $this->getJson('/api/v1/cash-register');

        $response->assertStatus(200);
    }

    public function test_member_cannot_access_cash_register(): void
    {
        $member = User::factory()->member()->create();
        Sanctum::actingAs($member);

        $response = $this->getJson('/api/v1/cash-register');

        $response->assertStatus(403);
    }

    // ========== ADMIN ONLY STORE MANAGEMENT ==========

    public function test_admin_can_access_admin_store_products(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/admin/store/products');

        $response->assertStatus(200);
    }

    public function test_trainer_cannot_access_admin_store_products(): void
    {
        $trainer = User::factory()->trainer()->create();
        Sanctum::actingAs($trainer);

        $response = $this->getJson('/api/v1/admin/store/products');

        $response->assertStatus(403);
    }

    public function test_member_cannot_access_admin_store_products(): void
    {
        $member = User::factory()->member()->create();
        Sanctum::actingAs($member);

        $response = $this->getJson('/api/v1/admin/store/products');

        $response->assertStatus(403);
    }

    // ========== TIME TRACKING ==========

    public function test_admin_can_access_time_tracking_admin(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/time-tracking/admin');

        $response->assertStatus(200);
    }

    public function test_trainer_cannot_access_time_tracking_admin(): void
    {
        $trainer = User::factory()->trainer()->create();
        Sanctum::actingAs($trainer);

        $response = $this->getJson('/api/v1/time-tracking/admin');

        $response->assertStatus(403);
    }

    // ========== TASK MANAGEMENT ==========

    public function test_admin_can_access_tasks(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/tasks');

        $response->assertStatus(200);
    }

    public function test_trainer_can_access_tasks(): void
    {
        $trainer = User::factory()->trainer()->create();
        Sanctum::actingAs($trainer);

        $response = $this->getJson('/api/v1/tasks');

        $response->assertStatus(200);
    }

    public function test_member_cannot_access_tasks(): void
    {
        $member = User::factory()->member()->create();
        Sanctum::actingAs($member);

        $response = $this->getJson('/api/v1/tasks');

        $response->assertStatus(403);
    }
}
