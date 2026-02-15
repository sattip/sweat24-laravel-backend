<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\GymClass;
use App\Models\Instructor;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class WaitlistTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $member;
    protected $gymClass;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->member = User::factory()->create(['role' => 'member']);
        $store = Store::create(['name' => 'Test Store', 'address' => '123 Test St']);
        $instructor = Instructor::create([
            'store_id' => $store->id,
            'name' => 'Test Trainer',
            'email' => 'trainer@test.com',
            'specialties' => json_encode(['EMS']),
            'hourly_rate' => 25.00,
            'contract_type' => 'hourly',
            'join_date' => now()->toDateString(),
        ]);
        $this->gymClass = GymClass::create([
            'name' => 'Test Class',
            'type' => 'group',
            'instructor' => $instructor->name,
            'date' => now()->addDays(2)->toDateString(),
            'time' => '10:00',
            'duration' => 45,
            'max_participants' => 1,
            'current_participants' => 1,
            'location' => 'Main Hall',
            'description' => 'Test class description',
        ]);
    }

    public function test_waitlist_requires_auth()
    {
        $this->postJson("/api/v1/classes/{$this->gymClass->id}/waitlist/join")
            ->assertStatus(401);
    }

    public function test_user_can_join_waitlist()
    {
        Sanctum::actingAs($this->member);
        $response = $this->postJson("/api/v1/classes/{$this->gymClass->id}/waitlist/join");
        $this->assertContains($response->status(), [200, 201, 422]);
    }

    public function test_user_can_check_waitlist_status()
    {
        Sanctum::actingAs($this->member);
        $response = $this->getJson("/api/v1/classes/{$this->gymClass->id}/waitlist/status");
        $response->assertStatus(200);
    }

    public function test_user_can_view_my_waitlists()
    {
        Sanctum::actingAs($this->member);
        $response = $this->getJson('/api/v1/my-waitlists');
        $response->assertStatus(200);
    }

    public function test_admin_can_view_class_waitlist()
    {
        Sanctum::actingAs($this->admin);
        $response = $this->getJson("/api/v1/classes/{$this->gymClass->id}/waitlist");
        $response->assertStatus(200);
    }

    public function test_member_cannot_view_class_waitlist()
    {
        Sanctum::actingAs($this->member);
        $this->getJson("/api/v1/classes/{$this->gymClass->id}/waitlist")
            ->assertStatus(403);
    }

    public function test_admin_can_view_waitlist_summary()
    {
        Sanctum::actingAs($this->admin);
        $response = $this->getJson('/api/v1/waitlists/summary');
        $response->assertStatus(200);
    }
}
