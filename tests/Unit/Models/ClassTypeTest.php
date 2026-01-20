<?php

namespace Tests\Unit\Models;

use App\Models\ClassType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_class_type(): void
    {
        $classType = ClassType::factory()->create();

        $this->assertDatabaseHas('class_types', [
            'id' => $classType->id,
            'name' => $classType->name,
        ]);
    }

    public function test_is_active_cast_to_boolean(): void
    {
        $classType = ClassType::factory()->create(['is_active' => true]);

        $this->assertIsBool($classType->is_active);
        $this->assertTrue($classType->is_active);
    }

    public function test_sort_order_cast_to_integer(): void
    {
        $classType = ClassType::factory()->create(['sort_order' => 5]);

        $this->assertIsInt($classType->sort_order);
        $this->assertEquals(5, $classType->sort_order);
    }

    public function test_active_scope(): void
    {
        ClassType::factory()->active()->count(2)->create();
        ClassType::factory()->inactive()->create();

        $activeTypes = ClassType::active()->get();

        $this->assertCount(2, $activeTypes);
    }

    public function test_ordered_scope(): void
    {
        ClassType::factory()->create(['sort_order' => 3, 'name' => 'Charlie']);
        ClassType::factory()->create(['sort_order' => 1, 'name' => 'Alpha']);
        ClassType::factory()->create(['sort_order' => 2, 'name' => 'Bravo']);

        $ordered = ClassType::ordered()->get();

        $this->assertEquals('Alpha', $ordered->first()->name);
        $this->assertEquals('Bravo', $ordered[1]->name);
        $this->assertEquals('Charlie', $ordered->last()->name);
    }

    public function test_active_state(): void
    {
        $classType = ClassType::factory()->active()->create();

        $this->assertTrue($classType->is_active);
    }

    public function test_inactive_state(): void
    {
        $classType = ClassType::factory()->inactive()->create();

        $this->assertFalse($classType->is_active);
    }

    public function test_fillable_fields(): void
    {
        $data = [
            'name' => 'Test Class Type',
            'value' => 'test_class_type',
            'description' => 'A test class type',
            'color' => '#FF5733',
            'is_active' => true,
            'sort_order' => 1,
        ];

        $classType = ClassType::create($data);

        $this->assertEquals('Test Class Type', $classType->name);
        $this->assertEquals('test_class_type', $classType->value);
        $this->assertEquals('#FF5733', $classType->color);
    }
}
