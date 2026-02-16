<?php

namespace Tests\Unit\Models;

use App\Models\FitnessLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FitnessLevelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_fitness_level(): void
    {
        $fitnessLevel = FitnessLevel::factory()->create();
        $this->assertDatabaseHas('fitness_levels', ['id' => $fitnessLevel->id]);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $fitnessLevel = FitnessLevel::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $fitnessLevel->user);
        $this->assertEquals($user->id, $fitnessLevel->user->id);
    }

    public function test_belongs_to_assessor(): void
    {
        $assessor = User::factory()->create();
        $fitnessLevel = FitnessLevel::factory()->create(['assessed_by' => $assessor->id]);

        $this->assertInstanceOf(User::class, $fitnessLevel->assessor);
        $this->assertEquals($assessor->id, $fitnessLevel->assessor->id);
    }

    public function test_beginner_state(): void
    {
        $fitnessLevel = FitnessLevel::factory()->beginner()->create();
        $this->assertEquals('beginner', $fitnessLevel->level);
    }

    public function test_intermediate_state(): void
    {
        $fitnessLevel = FitnessLevel::factory()->intermediate()->create();
        $this->assertEquals('intermediate', $fitnessLevel->level);
    }

    public function test_advanced_state(): void
    {
        $fitnessLevel = FitnessLevel::factory()->advanced()->create();
        $this->assertEquals('advanced', $fitnessLevel->level);
    }

    public function test_elite_state(): void
    {
        $fitnessLevel = FitnessLevel::factory()->elite()->create();
        $this->assertEquals('elite', $fitnessLevel->level);
    }

    public function test_level_label_attribute(): void
    {
        $fitnessLevel = FitnessLevel::factory()->beginner()->create();
        $this->assertEquals('Πολύ Αρχάριος', $fitnessLevel->level_label);
    }

    public function test_level_color_attribute(): void
    {
        $fitnessLevel = FitnessLevel::factory()->elite()->create();
        $this->assertEquals('purple', $fitnessLevel->level_color);
    }

    public function test_assessment_date_cast(): void
    {
        $fitnessLevel = FitnessLevel::factory()->create();
        $this->assertInstanceOf(\Carbon\Carbon::class, $fitnessLevel->assessment_date);
    }
}
