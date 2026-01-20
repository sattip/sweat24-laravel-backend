<?php

namespace Tests\Unit\Models;

use App\Models\ExerciseEquipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExerciseEquipmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Clear seeded data to avoid conflicts
        ExerciseEquipment::query()->delete();
    }

    public function test_can_create_exercise_equipment(): void
    {
        $equipment = ExerciseEquipment::factory()->create();
        $this->assertDatabaseHas('exercise_equipment', ['id' => $equipment->id]);
    }

    public function test_active_state(): void
    {
        $equipment = ExerciseEquipment::factory()->active()->create();
        $this->assertTrue($equipment->is_active);
    }

    public function test_inactive_state(): void
    {
        $equipment = ExerciseEquipment::factory()->inactive()->create();
        $this->assertFalse($equipment->is_active);
    }

    public function test_active_scope(): void
    {
        ExerciseEquipment::factory()->active()->count(2)->create();
        ExerciseEquipment::factory()->inactive()->create();

        $this->assertCount(2, ExerciseEquipment::active()->get());
    }

    public function test_ordered_scope(): void
    {
        ExerciseEquipment::factory()->create(['sort_order' => 3]);
        ExerciseEquipment::factory()->create(['sort_order' => 1]);
        ExerciseEquipment::factory()->create(['sort_order' => 2]);

        $ordered = ExerciseEquipment::ordered()->get();
        $this->assertEquals(1, $ordered->first()->sort_order);
    }

    public function test_sort_order_cast_to_integer(): void
    {
        $equipment = ExerciseEquipment::factory()->create();
        $this->assertIsInt($equipment->sort_order);
    }
}
