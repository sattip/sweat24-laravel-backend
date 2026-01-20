<?php

namespace Tests\Unit\Models;

use App\Models\Instructor;
use App\Models\Service;
use App\Models\TrialAppointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrialAppointmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_trial_appointment(): void
    {
        $appointment = TrialAppointment::factory()->create();
        $this->assertDatabaseHas('trial_appointments', ['id' => $appointment->id]);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $appointment = TrialAppointment::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $appointment->user);
        $this->assertEquals($user->id, $appointment->user->id);
    }

    public function test_belongs_to_service(): void
    {
        $service = Service::factory()->create();
        $appointment = TrialAppointment::factory()->create(['service_id' => $service->id]);

        $this->assertInstanceOf(Service::class, $appointment->service);
        $this->assertEquals($service->id, $appointment->service->id);
    }

    public function test_pending_state(): void
    {
        $appointment = TrialAppointment::factory()->pending()->create();
        $this->assertEquals('pending', $appointment->status);
    }

    public function test_confirmed_state(): void
    {
        $appointment = TrialAppointment::factory()->confirmed()->create();

        $this->assertEquals('confirmed', $appointment->status);
        $this->assertNotNull($appointment->confirmed_at);
    }

    public function test_completed_state(): void
    {
        $appointment = TrialAppointment::factory()->completed()->create();

        $this->assertEquals('completed', $appointment->status);
        $this->assertNotNull($appointment->completed_at);
    }

    public function test_cancelled_state(): void
    {
        $appointment = TrialAppointment::factory()->cancelled()->create();

        $this->assertEquals('cancelled', $appointment->status);
        $this->assertNotNull($appointment->cancelled_at);
    }

    public function test_no_show_state(): void
    {
        $appointment = TrialAppointment::factory()->noShow()->create();
        $this->assertEquals('no_show', $appointment->status);
    }

    public function test_with_instructor_state(): void
    {
        $appointment = TrialAppointment::factory()->withInstructor()->create();

        $this->assertNotNull($appointment->instructor_id);
        $this->assertInstanceOf(Instructor::class, $appointment->instructor);
    }

    public function test_past_state(): void
    {
        $appointment = TrialAppointment::factory()->past()->create();
        $this->assertTrue($appointment->appointment_date < now()->format('Y-m-d'));
    }

    public function test_upcoming_state(): void
    {
        $appointment = TrialAppointment::factory()->upcoming()->create();
        $this->assertTrue($appointment->appointment_date > now()->format('Y-m-d'));
    }

    public function test_price_is_positive(): void
    {
        $appointment = TrialAppointment::factory()->create();
        $this->assertGreaterThan(0, $appointment->price);
    }
}
