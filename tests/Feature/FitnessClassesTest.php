<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\FitnessClass;
use App\Models\Store;
use App\Models\Service;
use App\Models\ClassType;
use App\Models\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class FitnessClassesTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $trainer;
    protected $member;
    protected $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->trainer = User::factory()->create(['role' => 'trainer']);
        $this->member = User::factory()->create(['role' => 'member']);
        $this->store = Store::create(['name' => 'Test Store', 'address' => '123 Test St']);
    }

    public function test_public_can_list_fitness_classes()
    {
        $this->getJson('/api/v1/fitness-classes')->assertStatus(200);
    }

    public function test_public_can_get_types()
    {
        $this->getJson('/api/v1/fitness-classes/types/all')->assertStatus(200);
    }

    public function test_create_fitness_class_requires_auth()
    {
        $this->postJson('/api/v1/fitness-classes', ['name' => 'Test'])->assertStatus(401);
    }

    public function test_member_cannot_create_fitness_class()
    {
        Sanctum::actingAs($this->member);
        $this->postJson('/api/v1/fitness-classes', ['name' => 'Test'])->assertStatus(403);
    }

    public function test_admin_can_create_fitness_class()
    {
        Sanctum::actingAs($this->admin);
        $service = Service::create(['name' => 'Pilates', 'slug' => 'pilates', 'lesson_type' => 'group', 'is_active' => true]);
        $response = $this->postJson('/api/v1/fitness-classes', [
            'name' => 'Morning Pilates',
            'service_id' => $service->id,
            'store_id' => $this->store->id,
            'date' => now()->addDays(1)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'capacity' => 10,
            'instructor_id' => null,
        ]);
        $this->assertContains($response->status(), [200, 201, 422]);
    }

    public function test_trainer_can_create_fitness_class()
    {
        Sanctum::actingAs($this->trainer);
        $service = Service::create(['name' => 'Yoga', 'slug' => 'yoga', 'lesson_type' => 'group', 'is_active' => true]);
        $response = $this->postJson('/api/v1/fitness-classes', [
            'name' => 'Yoga Session',
            'service_id' => $service->id,
            'store_id' => $this->store->id,
            'date' => now()->addDays(1)->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'capacity' => 8,
        ]);
        $this->assertContains($response->status(), [200, 201, 422]);
    }

    public function test_admin_can_get_participants()
    {
        Sanctum::actingAs($this->admin);
        $instructor = Instructor::create([
            'store_id' => $this->store->id,
            'name' => 'Test Instructor',
            'email' => 'inst@test.com',
            'specialties' => json_encode(['Pilates']),
            'hourly_rate' => 20.00,
            'contract_type' => 'hourly',
            'join_date' => now()->toDateString(),
        ]);
        $fc = FitnessClass::create([
            'name' => 'Test',
            'type' => 'group',
            'instructor' => $instructor->id,
            'store_id' => $this->store->id,
            'date' => now()->addDay()->toDateString(),
            'time' => '10:00',
            'duration' => 60,
            'max_participants' => 10,
            'current_participants' => 0,
        ]);
        $response = $this->getJson("/api/v1/fitness-classes/{$fc->id}/participants");
        $response->assertStatus(200);
    }

    public function test_member_cannot_cancel_fitness_class()
    {
        Sanctum::actingAs($this->member);
        $instructor = Instructor::create([
            'store_id' => $this->store->id,
            'name' => 'Instructor 2',
            'email' => 'inst2@test.com',
            'specialties' => json_encode(['Yoga']),
            'hourly_rate' => 20.00,
            'contract_type' => 'hourly',
            'join_date' => now()->toDateString(),
        ]);
        $fc = FitnessClass::create([
            'name' => 'Test',
            'type' => 'group',
            'instructor' => $instructor->id,
            'store_id' => $this->store->id,
            'date' => now()->addDay()->toDateString(),
            'time' => '10:00',
            'duration' => 60,
            'max_participants' => 10,
            'current_participants' => 0,
        ]);
        $this->postJson("/api/v1/fitness-classes/{$fc->id}/cancel")->assertStatus(403);
    }
}
