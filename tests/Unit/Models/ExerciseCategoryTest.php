<?php

namespace Tests\Unit\Models;

use App\Models\ExerciseCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExerciseCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_exercise_category(): void
    {
        $category = ExerciseCategory::factory()->create();
        $this->assertDatabaseHas('exercise_categories', ['id' => $category->id]);
    }

    public function test_active_state(): void
    {
        $category = ExerciseCategory::factory()->active()->create();
        $this->assertTrue($category->is_active);
    }

    public function test_inactive_state(): void
    {
        $category = ExerciseCategory::factory()->inactive()->create();
        $this->assertFalse($category->is_active);
    }

    public function test_active_scope(): void
    {
        ExerciseCategory::factory()->active()->count(2)->create();
        ExerciseCategory::factory()->inactive()->create();

        $this->assertCount(2, ExerciseCategory::active()->get());
    }

    public function test_is_active_cast_to_boolean(): void
    {
        $category = ExerciseCategory::factory()->create();
        $this->assertIsBool($category->is_active);
    }
}
