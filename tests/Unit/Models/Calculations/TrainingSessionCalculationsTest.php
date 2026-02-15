<?php

namespace Tests\Unit\Models\Calculations;

use App\Models\TrainingExercise;
use App\Models\TrainingSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingSessionCalculationsTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Total Volume Calculation Tests
    // ==========================================

    public function test_calculates_total_volume_correctly(): void
    {
        $user = User::factory()->create();
        $session = TrainingSession::create([
            'user_id' => $user->id,
            'session_date' => now(),
            'duration_minutes' => 60,
            'session_type' => 'personal',
            'intensity' => 7,
        ]);

        // Create exercises with known values
        TrainingExercise::create([
            'training_session_id' => $session->id,
            'exercise_name' => 'Squat',
            'sets' => 4,
            'reps' => 10,
            'weight_kg' => 60,
            'order' => 1,
        ]);

        TrainingExercise::create([
            'training_session_id' => $session->id,
            'exercise_name' => 'Bench Press',
            'sets' => 3,
            'reps' => 8,
            'weight_kg' => 50,
            'order' => 2,
        ]);

        $session->refresh();
        $session->calculateTotalVolume();

        // Volume = (4 × 10 × 60) + (3 × 8 × 50) = 2400 + 1200 = 3600
        $this->assertEquals(3600, $session->total_volume);
    }

    public function test_calculates_volume_with_zero_weight(): void
    {
        $user = User::factory()->create();
        $session = TrainingSession::create([
            'user_id' => $user->id,
            'session_date' => now(),
            'duration_minutes' => 30,
            'session_type' => 'personal',
            'intensity' => 5,
        ]);

        // Bodyweight exercise (no weight)
        TrainingExercise::create([
            'training_session_id' => $session->id,
            'exercise_name' => 'Push-ups',
            'sets' => 3,
            'reps' => 15,
            'weight_kg' => 0,
            'order' => 1,
        ]);

        $session->refresh();
        $session->calculateTotalVolume();

        // Volume = 3 × 15 × 0 = 0
        $this->assertEquals(0, $session->total_volume);
    }

    public function test_calculates_volume_with_no_exercises(): void
    {
        $user = User::factory()->create();
        $session = TrainingSession::create([
            'user_id' => $user->id,
            'session_date' => now(),
            'duration_minutes' => 30,
            'session_type' => 'personal',
            'intensity' => 5,
        ]);

        $session->calculateTotalVolume();

        $this->assertEquals(0, $session->total_volume);
    }

    public function test_calculates_volume_with_decimal_weight(): void
    {
        $user = User::factory()->create();
        $session = TrainingSession::create([
            'user_id' => $user->id,
            'session_date' => now(),
            'duration_minutes' => 45,
            'session_type' => 'personal',
            'intensity' => 6,
        ]);

        TrainingExercise::create([
            'training_session_id' => $session->id,
            'exercise_name' => 'Dumbbell Curl',
            'sets' => 3,
            'reps' => 12,
            'weight_kg' => 7.5,
            'order' => 1,
        ]);

        $session->refresh();
        $session->calculateTotalVolume();

        // Volume = 3 × 12 × 7.5 = 270
        $this->assertEquals(270, $session->total_volume);
    }

    public function test_calculates_volume_for_multiple_exercises(): void
    {
        $user = User::factory()->create();
        $session = TrainingSession::create([
            'user_id' => $user->id,
            'session_date' => now(),
            'duration_minutes' => 60,
            'session_type' => 'personal',
            'intensity' => 8,
        ]);

        // 5 different exercises
        TrainingExercise::create([
            'training_session_id' => $session->id,
            'exercise_name' => 'Squat',
            'sets' => 4,
            'reps' => 8,
            'weight_kg' => 100,
            'order' => 1,
        ]); // 3200

        TrainingExercise::create([
            'training_session_id' => $session->id,
            'exercise_name' => 'Deadlift',
            'sets' => 3,
            'reps' => 5,
            'weight_kg' => 120,
            'order' => 2,
        ]); // 1800

        TrainingExercise::create([
            'training_session_id' => $session->id,
            'exercise_name' => 'Bench Press',
            'sets' => 4,
            'reps' => 10,
            'weight_kg' => 80,
            'order' => 3,
        ]); // 3200

        TrainingExercise::create([
            'training_session_id' => $session->id,
            'exercise_name' => 'Rows',
            'sets' => 3,
            'reps' => 12,
            'weight_kg' => 60,
            'order' => 4,
        ]); // 2160

        TrainingExercise::create([
            'training_session_id' => $session->id,
            'exercise_name' => 'Shoulder Press',
            'sets' => 3,
            'reps' => 10,
            'weight_kg' => 40,
            'order' => 5,
        ]); // 1200

        $session->refresh();
        $session->calculateTotalVolume();

        // Total = 3200 + 1800 + 3200 + 2160 + 1200 = 11560
        $this->assertEquals(11560, $session->total_volume);
    }

    // ==========================================
    // Intensity Label Tests
    // ==========================================

    public function test_intensity_label_low(): void
    {
        $user = User::factory()->create();

        $session1 = TrainingSession::create([
            'user_id' => $user->id,
            'session_date' => now(),
            'duration_minutes' => 30,
            'session_type' => 'personal',
            'intensity' => 1,
        ]);

        $session2 = TrainingSession::create([
            'user_id' => $user->id,
            'session_date' => now(),
            'duration_minutes' => 30,
            'session_type' => 'personal',
            'intensity' => 3,
        ]);

        $this->assertEquals('Χαμηλή', $session1->intensity_label);
        $this->assertEquals('Χαμηλή', $session2->intensity_label);
    }

    public function test_intensity_label_medium(): void
    {
        $user = User::factory()->create();

        $session1 = TrainingSession::create([
            'user_id' => $user->id,
            'session_date' => now(),
            'duration_minutes' => 30,
            'session_type' => 'personal',
            'intensity' => 4,
        ]);

        $session2 = TrainingSession::create([
            'user_id' => $user->id,
            'session_date' => now(),
            'duration_minutes' => 30,
            'session_type' => 'personal',
            'intensity' => 6,
        ]);

        $this->assertEquals('Μέτρια', $session1->intensity_label);
        $this->assertEquals('Μέτρια', $session2->intensity_label);
    }

    public function test_intensity_label_high(): void
    {
        $user = User::factory()->create();

        $session1 = TrainingSession::create([
            'user_id' => $user->id,
            'session_date' => now(),
            'duration_minutes' => 30,
            'session_type' => 'personal',
            'intensity' => 7,
        ]);

        $session2 = TrainingSession::create([
            'user_id' => $user->id,
            'session_date' => now(),
            'duration_minutes' => 30,
            'session_type' => 'personal',
            'intensity' => 10,
        ]);

        $this->assertEquals('Υψηλή', $session1->intensity_label);
        $this->assertEquals('Υψηλή', $session2->intensity_label);
    }

    // ==========================================
    // Session Type Label Tests
    // ==========================================

    public function test_session_type_labels(): void
    {
        $user = User::factory()->create();

        $personal = TrainingSession::create([
            'user_id' => $user->id,
            'session_date' => now(),
            'duration_minutes' => 60,
            'session_type' => 'personal',
            'intensity' => 7,
        ]);

        $semiPersonal = TrainingSession::create([
            'user_id' => $user->id,
            'session_date' => now(),
            'duration_minutes' => 60,
            'session_type' => 'semi_personal',
            'intensity' => 7,
        ]);

        $group = TrainingSession::create([
            'user_id' => $user->id,
            'session_date' => now(),
            'duration_minutes' => 60,
            'session_type' => 'group',
            'intensity' => 7,
        ]);

        $this->assertEquals('Personal', $personal->session_type_label);
        $this->assertEquals('Semi-Personal', $semiPersonal->session_type_label);
        $this->assertEquals('Group', $group->session_type_label);
    }

    // ==========================================
    // Scope Tests
    // ==========================================

    public function test_scope_for_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        TrainingSession::create([
            'user_id' => $user1->id,
            'session_date' => now(),
            'duration_minutes' => 60,
            'session_type' => 'personal',
            'intensity' => 7,
        ]);

        TrainingSession::create([
            'user_id' => $user1->id,
            'session_date' => now(),
            'duration_minutes' => 45,
            'session_type' => 'group',
            'intensity' => 5,
        ]);

        TrainingSession::create([
            'user_id' => $user2->id,
            'session_date' => now(),
            'duration_minutes' => 30,
            'session_type' => 'personal',
            'intensity' => 6,
        ]);

        $user1Sessions = TrainingSession::forUser($user1->id)->get();
        $user2Sessions = TrainingSession::forUser($user2->id)->get();

        $this->assertCount(2, $user1Sessions);
        $this->assertCount(1, $user2Sessions);
    }

    public function test_scope_between_dates(): void
    {
        $user = User::factory()->create();

        // Use explicit date strings for clarity
        $date10DaysAgo = '2026-01-10';
        $date5DaysAgo = '2026-01-15';
        $dateToday = '2026-01-20';
        $date7DaysAgo = '2026-01-13';

        // Session 1: 10 days ago (outside 7-day range)
        TrainingSession::create([
            'user_id' => $user->id,
            'session_date' => $date10DaysAgo,
            'duration_minutes' => 60,
            'session_type' => 'personal',
            'intensity' => 7,
        ]);

        // Session 2: 5 days ago (inside 7-day range)
        TrainingSession::create([
            'user_id' => $user->id,
            'session_date' => $date5DaysAgo,
            'duration_minutes' => 45,
            'session_type' => 'group',
            'intensity' => 5,
        ]);

        // Session 3: today (inside 7-day range)
        TrainingSession::create([
            'user_id' => $user->id,
            'session_date' => $dateToday,
            'duration_minutes' => 30,
            'session_type' => 'personal',
            'intensity' => 6,
        ]);

        // Query for sessions between 7 days ago and today
        // Expected: Sessions from 2026-01-15 and 2026-01-20 (2 records)
        $lastWeekSessions = TrainingSession::betweenDates(
            $date7DaysAgo,
            $dateToday
        )->get();

        $this->assertCount(2, $lastWeekSessions);
    }

    public function test_scope_by_intensity(): void
    {
        $user = User::factory()->create();

        TrainingSession::create([
            'user_id' => $user->id,
            'session_date' => now(),
            'duration_minutes' => 60,
            'session_type' => 'personal',
            'intensity' => 3, // Low
        ]);

        TrainingSession::create([
            'user_id' => $user->id,
            'session_date' => now(),
            'duration_minutes' => 45,
            'session_type' => 'group',
            'intensity' => 5, // Medium
        ]);

        TrainingSession::create([
            'user_id' => $user->id,
            'session_date' => now(),
            'duration_minutes' => 30,
            'session_type' => 'personal',
            'intensity' => 8, // High
        ]);

        $highIntensity = TrainingSession::byIntensity(7, 10)->get();
        $mediumIntensity = TrainingSession::byIntensity(4, 6)->get();
        $lowIntensity = TrainingSession::byIntensity(1, 3)->get();

        $this->assertCount(1, $highIntensity);
        $this->assertCount(1, $mediumIntensity);
        $this->assertCount(1, $lowIntensity);
    }
}
