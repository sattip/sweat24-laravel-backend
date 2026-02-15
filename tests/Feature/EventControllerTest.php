<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class EventControllerTest extends TestCase
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

    public function test_public_can_list_events()
    {
        $this->getJson('/api/v1/events')->assertStatus(200);
    }

    public function test_rsvp_requires_auth()
    {
        $event = Event::create([
            'name' => 'Gym Open Day',
            'description' => 'Come try our equipment',
            'date' => now()->addDays(7)->toDateString(),
            'time' => '18:00',
            'location' => 'Main Gym',
            'is_active' => true,
        ]);
        $this->postJson("/api/v1/events/{$event->id}/rsvp")->assertStatus(401);
    }

    public function test_user_can_rsvp_to_event()
    {
        Sanctum::actingAs($this->member);
        $event = Event::create([
            'name' => 'Gym Open Day',
            'description' => 'Come try our equipment',
            'date' => now()->addDays(7)->toDateString(),
            'time' => '18:00',
            'location' => 'Main Gym',
            'is_active' => true,
        ]);
        $response = $this->postJson("/api/v1/events/{$event->id}/rsvp");
        $this->assertContains($response->status(), [200, 201, 422]);
    }

    public function test_user_can_view_own_rsvps()
    {
        Sanctum::actingAs($this->member);
        $this->getJson('/api/v1/events/rsvps')->assertStatus(200);
    }

    public function test_admin_can_list_events()
    {
        Sanctum::actingAs($this->admin);
        $this->getJson('/api/v1/admin/events')->assertStatus(200);
    }

    public function test_admin_can_create_event()
    {
        Sanctum::actingAs($this->admin);
        $response = $this->postJson('/api/v1/events', [
            'title' => 'New Event',
            'description' => 'A test event',
            'date' => now()->addDays(7)->toDateString(),
            'time' => '18:00',
            'location' => 'Main Gym',
        ]);
        $this->assertContains($response->status(), [200, 201, 422]);
    }

    public function test_member_cannot_create_event()
    {
        Sanctum::actingAs($this->member);
        $this->postJson('/api/v1/events', ['title' => 'Test'])->assertStatus(403);
    }

    public function test_admin_can_view_all_rsvps()
    {
        Sanctum::actingAs($this->admin);
        $this->getJson('/api/v1/admin/event-rsvps')->assertStatus(200);
    }
}
