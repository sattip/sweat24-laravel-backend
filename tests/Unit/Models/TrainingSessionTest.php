<?php

namespace Tests\Unit\Models;

use App\Models\Booking;
use App\Models\TrainingExercise;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_training_session(): void
    {
        $user = User::factory()->create();
        $session = TrainingSession::factory()->create(['user_id' => $user->id]);

        $this->assertDatabaseHas('training_sessions', [
            'id' => $session->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_session_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $session = TrainingSession::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $session->user);
        $this->assertEquals($user->id, $session->user->id);
    }

    public function test_session_belongs_to_trainer(): void
    {
        $trainer = User::factory()->create();
        $session = TrainingSession::factory()->create(['trainer_id' => $trainer->id]);

        $this->assertInstanceOf(User::class, $session->trainer);
        $this->assertEquals($trainer->id, $session->trainer->id);
    }

    public function test_session_belongs_to_booking(): void
    {
        $booking = Booking::factory()->create();
        $session = TrainingSession::factory()->create(['booking_id' => $booking->id]);

        $this->assertInstanceOf(Booking::class, $session->booking);
        $this->assertEquals($booking->id, $session->booking->id);
    }

    public function test_session_has_many_exercises(): void
    {
        $session = TrainingSession::factory()->create();
        TrainingExercise::factory()->count(3)->create(['training_session_id' => $session->id]);

        $this->assertCount(3, $session->exercises);
        $this->assertInstanceOf(TrainingExercise::class, $session->exercises->first());
    }

    public function test_intensity_label_low(): void
    {
        $session = TrainingSession::factory()->create(['intensity' => 2]);

        $this->assertEquals('Χαμηλή', $session->intensity_label);
    }

    public function test_intensity_label_medium(): void
    {
        $session = TrainingSession::factory()->create(['intensity' => 5]);

        $this->assertEquals('Μέτρια', $session->intensity_label);
    }

    public function test_intensity_label_high(): void
    {
        $session = TrainingSession::factory()->create(['intensity' => 8]);

        $this->assertEquals('Υψηλή', $session->intensity_label);
    }

    public function test_session_type_label_personal(): void
    {
        $session = TrainingSession::factory()->personal()->create();

        $this->assertEquals('Personal', $session->session_type_label);
    }

    public function test_session_type_label_semi_personal(): void
    {
        $session = TrainingSession::factory()->semiPersonal()->create();

        $this->assertEquals('Semi-Personal', $session->session_type_label);
    }

    public function test_session_type_label_group(): void
    {
        $session = TrainingSession::factory()->group()->create();

        $this->assertEquals('Group', $session->session_type_label);
    }

    public function test_for_user_scope(): void
    {
        $user = User::factory()->create();
        TrainingSession::factory()->count(2)->create(['user_id' => $user->id]);
        TrainingSession::factory()->create();

        $sessions = TrainingSession::forUser($user->id)->get();

        $this->assertCount(2, $sessions);
    }

    public function test_for_trainer_scope(): void
    {
        $trainer = User::factory()->create();
        TrainingSession::factory()->count(2)->create(['trainer_id' => $trainer->id]);
        TrainingSession::factory()->create();

        $sessions = TrainingSession::forTrainer($trainer->id)->get();

        $this->assertCount(2, $sessions);
    }

    public function test_between_dates_scope(): void
    {
        TrainingSession::factory()->create(['session_date' => '2026-01-15']);
        TrainingSession::factory()->create(['session_date' => '2026-01-20']);
        TrainingSession::factory()->create(['session_date' => '2026-02-15']);

        $sessions = TrainingSession::betweenDates('2026-01-10', '2026-01-25')->get();

        $this->assertCount(2, $sessions);
    }

    public function test_by_intensity_scope(): void
    {
        TrainingSession::factory()->create(['intensity' => 3]);
        TrainingSession::factory()->create(['intensity' => 5]);
        TrainingSession::factory()->create(['intensity' => 8]);

        $sessions = TrainingSession::byIntensity(4, 6)->get();

        $this->assertCount(1, $sessions);
    }

    public function test_session_date_cast_to_date(): void
    {
        $session = TrainingSession::factory()->create(['session_date' => '2026-02-15']);

        $this->assertInstanceOf(\Carbon\Carbon::class, $session->session_date);
        $this->assertEquals('2026-02-15', $session->session_date->format('Y-m-d'));
    }

    public function test_muscle_groups_cast_to_array(): void
    {
        $session = TrainingSession::factory()->create(['muscle_groups' => ['chest', 'back']]);

        $this->assertIsArray($session->muscle_groups);
        $this->assertContains('chest', $session->muscle_groups);
    }

    public function test_boolean_casts(): void
    {
        $session = TrainingSession::factory()->create([
            'includes_cardio' => true,
            'includes_mobility' => true,
        ]);

        $this->assertIsBool($session->includes_cardio);
        $this->assertTrue($session->includes_cardio);
        $this->assertTrue($session->includes_mobility);
    }

    public function test_total_body_state(): void
    {
        $session = TrainingSession::factory()->totalBody()->create();

        $this->assertTrue($session->is_total_body);
        $this->assertCount(6, $session->muscle_groups);
    }

    public function test_with_cardio_state(): void
    {
        $session = TrainingSession::factory()->withCardio()->create();

        $this->assertTrue($session->includes_cardio);
    }
}
