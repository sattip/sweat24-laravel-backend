<?php

namespace Tests\Unit\Models;

use App\Models\AppointmentRequest;
use App\Models\Instructor;
use App\Models\SpecializedService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_appointment_request(): void
    {
        $request = AppointmentRequest::factory()->create();
        $this->assertDatabaseHas('appointment_requests', ['id' => $request->id]);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $request = AppointmentRequest::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $request->user);
        $this->assertEquals($user->id, $request->user->id);
    }

    public function test_belongs_to_specialized_service(): void
    {
        $service = SpecializedService::factory()->create();
        $request = AppointmentRequest::factory()->create(['specialized_service_id' => $service->id]);

        $this->assertInstanceOf(SpecializedService::class, $request->specializedService);
        $this->assertEquals($service->id, $request->specializedService->id);
    }

    public function test_belongs_to_instructor(): void
    {
        $instructor = Instructor::factory()->create();
        $request = AppointmentRequest::factory()->withInstructor()->create(['instructor_id' => $instructor->id]);

        $this->assertInstanceOf(Instructor::class, $request->instructor);
        $this->assertEquals($instructor->id, $request->instructor->id);
    }

    public function test_pending_state(): void
    {
        $request = AppointmentRequest::factory()->pending()->create();
        $this->assertEquals('pending', $request->status);
    }

    public function test_confirmed_state(): void
    {
        $request = AppointmentRequest::factory()->confirmed()->create();

        $this->assertEquals('confirmed', $request->status);
        $this->assertNotNull($request->confirmed_date);
        $this->assertNotNull($request->confirmed_time);
    }

    public function test_cancelled_state(): void
    {
        $request = AppointmentRequest::factory()->cancelled()->create();
        $this->assertEquals('cancelled', $request->status);
    }

    public function test_completed_state(): void
    {
        $request = AppointmentRequest::factory()->completed()->create();
        $this->assertEquals('completed', $request->status);
    }

    public function test_preferred_time_slots_cast_to_array(): void
    {
        $request = AppointmentRequest::factory()->create();

        $this->assertIsArray($request->preferred_time_slots);
        $this->assertNotEmpty($request->preferred_time_slots);
    }

    public function test_with_instructor_state(): void
    {
        $request = AppointmentRequest::factory()->withInstructor()->create();

        $this->assertNotNull($request->instructor_id);
        $this->assertInstanceOf(Instructor::class, $request->instructor);
    }
}
