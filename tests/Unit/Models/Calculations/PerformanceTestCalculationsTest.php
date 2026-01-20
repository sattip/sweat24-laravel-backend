<?php

namespace Tests\Unit\Models\Calculations;

use App\Models\PerformanceTest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformanceTestCalculationsTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Strength Improvement Calculation Tests
    // ==========================================

    public function test_calculates_strength_improvement_correctly(): void
    {
        $user = User::factory()->create();

        // Previous test
        PerformanceTest::create([
            'user_id' => $user->id,
            'exercise_name' => 'Bench Press',
            'test_date' => '2026-01-01',
            'weight_kg' => 80,
            'reps' => 10,
            'category' => 'strength',
        ]);

        // Current test with improvement
        $currentTest = PerformanceTest::create([
            'user_id' => $user->id,
            'exercise_name' => 'Bench Press',
            'test_date' => '2026-01-15',
            'weight_kg' => 85,
            'reps' => 10,
            'category' => 'strength',
        ]);

        $currentTest->calculateImprovement();

        // Previous volume = 80 × 10 = 800
        // Current volume = 85 × 10 = 850
        // Improvement = (850 - 800) / 800 × 100 = 6.25%
        $this->assertEquals(6.25, $currentTest->improvement_percentage);
    }

    public function test_calculates_strength_improvement_with_more_reps(): void
    {
        $user = User::factory()->create();

        PerformanceTest::create([
            'user_id' => $user->id,
            'exercise_name' => 'Squat',
            'test_date' => '2026-01-01',
            'weight_kg' => 100,
            'reps' => 8,
            'category' => 'strength',
        ]);

        $currentTest = PerformanceTest::create([
            'user_id' => $user->id,
            'exercise_name' => 'Squat',
            'test_date' => '2026-01-15',
            'weight_kg' => 100,
            'reps' => 10,
            'category' => 'strength',
        ]);

        $currentTest->calculateImprovement();

        // Previous volume = 100 × 8 = 800
        // Current volume = 100 × 10 = 1000
        // Improvement = (1000 - 800) / 800 × 100 = 25%
        $this->assertEquals(25.00, $currentTest->improvement_percentage);
    }

    public function test_calculates_negative_improvement(): void
    {
        $user = User::factory()->create();

        PerformanceTest::create([
            'user_id' => $user->id,
            'exercise_name' => 'Deadlift',
            'test_date' => '2026-01-01',
            'weight_kg' => 120,
            'reps' => 5,
            'category' => 'strength',
        ]);

        $currentTest = PerformanceTest::create([
            'user_id' => $user->id,
            'exercise_name' => 'Deadlift',
            'test_date' => '2026-01-15',
            'weight_kg' => 100,
            'reps' => 5,
            'category' => 'strength',
        ]);

        $currentTest->calculateImprovement();

        // Previous volume = 120 × 5 = 600
        // Current volume = 100 × 5 = 500
        // Improvement = (500 - 600) / 600 × 100 = -16.67%
        $this->assertEqualsWithDelta(-16.67, $currentTest->improvement_percentage, 0.01);
    }

    // ==========================================
    // Endurance Improvement Calculation Tests
    // ==========================================

    public function test_calculates_endurance_improvement_correctly(): void
    {
        $user = User::factory()->create();

        // Previous test - slower
        PerformanceTest::create([
            'user_id' => $user->id,
            'exercise_name' => 'Plank',
            'test_date' => '2026-01-01',
            'time_seconds' => 120,
            'category' => 'endurance',
        ]);

        // Current test - faster (improvement)
        $currentTest = PerformanceTest::create([
            'user_id' => $user->id,
            'exercise_name' => 'Plank',
            'test_date' => '2026-01-15',
            'time_seconds' => 100,
            'category' => 'endurance',
        ]);

        $currentTest->calculateImprovement();

        // For endurance: lower time is better
        // Improvement = (120 - 100) / 120 × 100 = 16.67%
        $this->assertEqualsWithDelta(16.67, $currentTest->improvement_percentage, 0.01);
    }

    public function test_calculates_endurance_decline(): void
    {
        $user = User::factory()->create();

        PerformanceTest::create([
            'user_id' => $user->id,
            'exercise_name' => 'Wall Sit',
            'test_date' => '2026-01-01',
            'time_seconds' => 60,
            'category' => 'endurance',
        ]);

        $currentTest = PerformanceTest::create([
            'user_id' => $user->id,
            'exercise_name' => 'Wall Sit',
            'test_date' => '2026-01-15',
            'time_seconds' => 90,
            'category' => 'endurance',
        ]);

        $currentTest->calculateImprovement();

        // For endurance: higher time is worse
        // Improvement = (60 - 90) / 60 × 100 = -50%
        $this->assertEquals(-50.00, $currentTest->improvement_percentage);
    }

    // ==========================================
    // No Previous Test Tests
    // ==========================================

    public function test_improvement_null_when_no_previous_test(): void
    {
        $user = User::factory()->create();

        $test = PerformanceTest::create([
            'user_id' => $user->id,
            'exercise_name' => 'Bench Press',
            'test_date' => '2026-01-15',
            'weight_kg' => 80,
            'reps' => 10,
            'category' => 'strength',
        ]);

        $test->calculateImprovement();

        $this->assertNull($test->improvement_percentage);
    }

    public function test_uses_most_recent_previous_test(): void
    {
        $user = User::factory()->create();

        // Oldest test
        PerformanceTest::create([
            'user_id' => $user->id,
            'exercise_name' => 'Bench Press',
            'test_date' => '2026-01-01',
            'weight_kg' => 60,
            'reps' => 10,
            'category' => 'strength',
        ]);

        // Middle test (should be used as previous)
        PerformanceTest::create([
            'user_id' => $user->id,
            'exercise_name' => 'Bench Press',
            'test_date' => '2026-01-10',
            'weight_kg' => 70,
            'reps' => 10,
            'category' => 'strength',
        ]);

        // Current test
        $currentTest = PerformanceTest::create([
            'user_id' => $user->id,
            'exercise_name' => 'Bench Press',
            'test_date' => '2026-01-15',
            'weight_kg' => 80,
            'reps' => 10,
            'category' => 'strength',
        ]);

        $currentTest->calculateImprovement();

        // Previous volume = 70 × 10 = 700 (from middle test)
        // Current volume = 80 × 10 = 800
        // Improvement = (800 - 700) / 700 × 100 = 14.29%
        $this->assertEqualsWithDelta(14.29, $currentTest->improvement_percentage, 0.01);
    }

    // ==========================================
    // Personal Record (PR) Tests - Strength
    // ==========================================

    public function test_first_test_is_pr(): void
    {
        $user = User::factory()->create();

        $test = PerformanceTest::create([
            'user_id' => $user->id,
            'exercise_name' => 'Bench Press',
            'test_date' => '2026-01-15',
            'weight_kg' => 80,
            'reps' => 10,
            'category' => 'strength',
        ]);

        $test->checkIfPR();

        $this->assertTrue($test->is_pr);
    }

    public function test_higher_volume_is_pr_for_strength(): void
    {
        $user = User::factory()->create();

        // Previous test - lower volume
        $previousTest = PerformanceTest::create([
            'user_id' => $user->id,
            'exercise_name' => 'Squat',
            'test_date' => '2026-01-01',
            'weight_kg' => 100,
            'reps' => 5,
            'category' => 'strength',
            'is_pr' => true,
        ]);

        // New test - higher volume (new PR)
        $newTest = PerformanceTest::create([
            'user_id' => $user->id,
            'exercise_name' => 'Squat',
            'test_date' => '2026-01-15',
            'weight_kg' => 110,
            'reps' => 5,
            'category' => 'strength',
        ]);

        $newTest->checkIfPR();

        $this->assertTrue($newTest->is_pr);
        $this->assertFalse($previousTest->fresh()->is_pr);
    }

    public function test_lower_volume_is_not_pr_for_strength(): void
    {
        $user = User::factory()->create();

        // Previous test - higher volume (PR)
        $prTest = PerformanceTest::create([
            'user_id' => $user->id,
            'exercise_name' => 'Deadlift',
            'test_date' => '2026-01-01',
            'weight_kg' => 150,
            'reps' => 5,
            'category' => 'strength',
            'is_pr' => true,
        ]);

        // New test - lower volume (not PR)
        $newTest = PerformanceTest::create([
            'user_id' => $user->id,
            'exercise_name' => 'Deadlift',
            'test_date' => '2026-01-15',
            'weight_kg' => 120,
            'reps' => 5,
            'category' => 'strength',
        ]);

        $newTest->checkIfPR();

        $this->assertFalse($newTest->is_pr);
        $this->assertTrue($prTest->fresh()->is_pr);
    }

    // ==========================================
    // Personal Record (PR) Tests - Endurance
    // ==========================================

    public function test_lower_time_is_pr_for_endurance(): void
    {
        $user = User::factory()->create();

        // Previous test - higher time
        $previousTest = PerformanceTest::create([
            'user_id' => $user->id,
            'exercise_name' => '1-Mile Run',
            'test_date' => '2026-01-01',
            'time_seconds' => 420,
            'category' => 'endurance',
            'is_pr' => true,
        ]);

        // New test - lower time (new PR)
        $newTest = PerformanceTest::create([
            'user_id' => $user->id,
            'exercise_name' => '1-Mile Run',
            'test_date' => '2026-01-15',
            'time_seconds' => 390,
            'category' => 'endurance',
        ]);

        $newTest->checkIfPR();

        $this->assertTrue($newTest->is_pr);
        $this->assertFalse($previousTest->fresh()->is_pr);
    }

    public function test_higher_time_is_not_pr_for_endurance(): void
    {
        $user = User::factory()->create();

        // Previous test - lower time (PR)
        $prTest = PerformanceTest::create([
            'user_id' => $user->id,
            'exercise_name' => 'Plank Hold',
            'test_date' => '2026-01-01',
            'time_seconds' => 180,
            'category' => 'endurance',
            'is_pr' => true,
        ]);

        // New test - higher time (not PR for endurance where lower is better)
        $newTest = PerformanceTest::create([
            'user_id' => $user->id,
            'exercise_name' => 'Plank Hold',
            'test_date' => '2026-01-15',
            'time_seconds' => 200,
            'category' => 'endurance',
        ]);

        $newTest->checkIfPR();

        // Note: For endurance, LOWER time is better
        $this->assertFalse($newTest->is_pr);
        $this->assertTrue($prTest->fresh()->is_pr);
    }

    // ==========================================
    // Exercise-Specific Tests
    // ==========================================

    public function test_pr_is_exercise_specific(): void
    {
        $user = User::factory()->create();

        // Bench Press PR
        $benchPR = PerformanceTest::create([
            'user_id' => $user->id,
            'exercise_name' => 'Bench Press',
            'test_date' => '2026-01-01',
            'weight_kg' => 100,
            'reps' => 5,
            'category' => 'strength',
            'is_pr' => true,
        ]);

        // Squat test - different exercise
        $squatTest = PerformanceTest::create([
            'user_id' => $user->id,
            'exercise_name' => 'Squat',
            'test_date' => '2026-01-15',
            'weight_kg' => 80,
            'reps' => 5,
            'category' => 'strength',
        ]);

        $squatTest->checkIfPR();

        // Squat should be PR for squat, bench should still be PR for bench
        $this->assertTrue($squatTest->is_pr);
        $this->assertTrue($benchPR->fresh()->is_pr);
    }

    public function test_pr_is_user_specific(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // User 1's PR
        $user1PR = PerformanceTest::create([
            'user_id' => $user1->id,
            'exercise_name' => 'Bench Press',
            'test_date' => '2026-01-01',
            'weight_kg' => 120,
            'reps' => 5,
            'category' => 'strength',
            'is_pr' => true,
        ]);

        // User 2's test (lower volume but should still be their PR)
        $user2Test = PerformanceTest::create([
            'user_id' => $user2->id,
            'exercise_name' => 'Bench Press',
            'test_date' => '2026-01-15',
            'weight_kg' => 80,
            'reps' => 5,
            'category' => 'strength',
        ]);

        $user2Test->checkIfPR();

        $this->assertTrue($user2Test->is_pr);
        $this->assertTrue($user1PR->fresh()->is_pr); // User 1's PR unchanged
    }

    // ==========================================
    // Core Category Tests
    // ==========================================

    public function test_core_category_uses_volume_calculation(): void
    {
        $user = User::factory()->create();

        PerformanceTest::create([
            'user_id' => $user->id,
            'exercise_name' => 'Weighted Plank',
            'test_date' => '2026-01-01',
            'weight_kg' => 10,
            'reps' => 3,
            'category' => 'core',
        ]);

        $currentTest = PerformanceTest::create([
            'user_id' => $user->id,
            'exercise_name' => 'Weighted Plank',
            'test_date' => '2026-01-15',
            'weight_kg' => 12,
            'reps' => 3,
            'category' => 'core',
        ]);

        $currentTest->calculateImprovement();

        // Previous volume = 10 × 3 = 30
        // Current volume = 12 × 3 = 36
        // Improvement = (36 - 30) / 30 × 100 = 20%
        $this->assertEquals(20.00, $currentTest->improvement_percentage);
    }

    // ==========================================
    // Edge Cases
    // ==========================================

    public function test_handles_null_weight(): void
    {
        $user = User::factory()->create();

        PerformanceTest::create([
            'user_id' => $user->id,
            'exercise_name' => 'Push-ups',
            'test_date' => '2026-01-01',
            'weight_kg' => null,
            'reps' => 20,
            'category' => 'strength',
        ]);

        $currentTest = PerformanceTest::create([
            'user_id' => $user->id,
            'exercise_name' => 'Push-ups',
            'test_date' => '2026-01-15',
            'weight_kg' => null,
            'reps' => 25,
            'category' => 'strength',
        ]);

        $currentTest->calculateImprovement();

        // When previous weight is null/0, volume comparison isn't meaningful
        // Implementation returns null in this case
        $this->assertNull($currentTest->improvement_percentage);
    }

    public function test_handles_null_reps(): void
    {
        $user = User::factory()->create();

        PerformanceTest::create([
            'user_id' => $user->id,
            'exercise_name' => '1RM Test',
            'test_date' => '2026-01-01',
            'weight_kg' => 100,
            'reps' => null,
            'category' => 'strength',
        ]);

        $currentTest = PerformanceTest::create([
            'user_id' => $user->id,
            'exercise_name' => '1RM Test',
            'test_date' => '2026-01-15',
            'weight_kg' => 110,
            'reps' => null,
            'category' => 'strength',
        ]);

        $currentTest->calculateImprovement();

        // With null reps, defaults to 1
        // Previous = 100 × 1 = 100
        // Current = 110 × 1 = 110
        // Improvement = 10%
        $this->assertEquals(10.00, $currentTest->improvement_percentage);
    }
}
