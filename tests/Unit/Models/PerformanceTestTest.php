<?php

namespace Tests\Unit\Models;

use App\Models\PerformanceTest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformanceTestTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_performance_test(): void
    {
        $test = PerformanceTest::factory()->create();
        $this->assertDatabaseHas('performance_tests', ['id' => $test->id]);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $test = PerformanceTest::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $test->user);
        $this->assertEquals($user->id, $test->user->id);
    }

    public function test_strength_state(): void
    {
        $test = PerformanceTest::factory()->strength()->create();

        $this->assertEquals('strength', $test->category);
        $this->assertNull($test->time_seconds);
    }

    public function test_endurance_state(): void
    {
        $test = PerformanceTest::factory()->endurance()->create();

        $this->assertEquals('endurance', $test->category);
        $this->assertNotNull($test->time_seconds);
        $this->assertNull($test->weight_kg);
    }

    public function test_core_state(): void
    {
        $test = PerformanceTest::factory()->core()->create();

        $this->assertEquals('core', $test->category);
        $this->assertEquals('Plank', $test->exercise_name);
        $this->assertNotNull($test->time_seconds);
    }

    public function test_personal_record_state(): void
    {
        $test = PerformanceTest::factory()->personalRecord()->create();
        $this->assertTrue($test->is_pr);
    }

    public function test_with_trainer_state(): void
    {
        $test = PerformanceTest::factory()->withTrainer()->create();
        $this->assertNotNull($test->trainer_id);
    }

    public function test_with_improvement_state(): void
    {
        $test = PerformanceTest::factory()->withImprovement()->create();
        $this->assertNotNull($test->improvement_percentage);
    }

    public function test_reps_is_between_1_and_20(): void
    {
        $test = PerformanceTest::factory()->strength()->create();

        $this->assertGreaterThanOrEqual(1, $test->reps);
        $this->assertLessThanOrEqual(20, $test->reps);
    }
}
