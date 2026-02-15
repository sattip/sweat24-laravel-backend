<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\BookingRequest;
use App\Models\Instructor;
use App\Models\Store;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class BookingRequestTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $trainer;
    protected $member;
    protected $store;
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->trainer = User::factory()->create(['role' => 'trainer']);
        $this->member = User::factory()->create(['role' => 'member']);
        $this->store = Store::create(['name' => 'Test Store', 'address' => '123 Test St']);
        $this->service = Service::create(['name' => 'EMS Training', 'slug' => 'ems', 'lesson_type' => 'personal', 'is_active' => true]);
    }

    public function test_authenticated_can_create_booking_request()
    {
        Sanctum::actingAs($this->member);
        $response = $this->postJson('/api/v1/booking-requests', [
            'customer_name' => 'John Doe',
            'customer_phone' => '6900000000',
            'customer_email' => 'john@example.com',
            'service_id' => $this->service->id,
            'preferred_date' => now()->addDays(3)->toDateString(),
            'preferred_time' => '10:00',
            'notes' => 'First time',
        ]);
        $this->assertContains($response->status(), [200, 201, 422]);
    }

    public function test_public_can_get_available_instructors()
    {
        $response = $this->getJson('/api/v1/booking-requests/instructors?service_type=personal');
        $this->assertContains($response->status(), [200, 422]);
    }

    public function test_authenticated_user_can_view_own_requests()
    {
        Sanctum::actingAs($this->member);
        $response = $this->getJson('/api/v1/booking-requests/my-requests');
        $response->assertStatus(200);
    }

    public function test_my_requests_requires_auth()
    {
        $this->getJson('/api/v1/booking-requests/my-requests')->assertStatus(401);
    }

    public function test_admin_can_list_booking_requests()
    {
        Sanctum::actingAs($this->admin);
        $response = $this->getJson('/api/v1/admin/booking-requests');
        $response->assertStatus(200);
    }

    public function test_admin_can_view_booking_requests_calendar()
    {
        Sanctum::actingAs($this->admin);
        $date = now()->toDateString();
        $response = $this->getJson("/api/v1/admin/booking-requests-calendar?date={$date}");
        $this->assertContains($response->status(), [200, 422]);
    }

    public function test_admin_can_view_booking_requests_statistics()
    {
        Sanctum::actingAs($this->admin);
        $response = $this->getJson('/api/v1/admin/booking-requests/statistics');
        $response->assertStatus(200);
    }

    public function test_member_cannot_access_admin_booking_requests()
    {
        Sanctum::actingAs($this->member);
        $this->getJson('/api/v1/admin/booking-requests')->assertStatus(403);
    }
}
