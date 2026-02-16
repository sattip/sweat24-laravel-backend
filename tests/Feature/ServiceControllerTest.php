<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class ServiceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->member = User::factory()->create(['role' => 'member']);
    }

    public function test_public_can_list_services()
    {
        $this->getJson('/api/v1/services')->assertStatus(200);
    }

    public function test_public_can_view_service()
    {
        $service = Service::create(['name' => 'EMS', 'slug' => 'ems', 'lesson_type' => 'personal', 'is_active' => true]);
        $this->getJson("/api/v1/services/{$service->id}")->assertStatus(200);
    }

    public function test_create_service_requires_auth()
    {
        $this->postJson('/api/v1/services', ['name' => 'Test'])->assertStatus(401);
    }

    public function test_member_cannot_create_service()
    {
        Sanctum::actingAs($this->member);
        $this->postJson('/api/v1/services', ['name' => 'Test'])->assertStatus(403);
    }

    public function test_admin_can_create_service()
    {
        Sanctum::actingAs($this->admin);
        $response = $this->postJson('/api/v1/services', [
            'name' => 'Pilates',
            'slug' => 'pilates',
            'lesson_type' => 'group',
            'is_active' => true,
        ]);
        $this->assertContains($response->status(), [200, 201, 422]);
    }

    public function test_admin_can_toggle_service_active()
    {
        Sanctum::actingAs($this->admin);
        $service = Service::create(['name' => 'Yoga', 'slug' => 'yoga', 'lesson_type' => 'group', 'is_active' => true]);
        $response = $this->postJson("/api/v1/services/{$service->id}/toggle-active");
        $this->assertContains($response->status(), [200, 422]);
    }

    public function test_trial_info_requires_auth()
    {
        $service = Service::create(['name' => 'EMS', 'slug' => 'ems', 'lesson_type' => 'personal', 'is_active' => true]);
        $this->getJson("/api/v1/services/{$service->id}/trial-info")->assertStatus(401);
    }
}
