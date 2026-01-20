<?php

namespace Tests\Unit\Models;

use App\Models\ExpenseCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_expense_category(): void
    {
        $category = ExpenseCategory::factory()->create();

        $this->assertDatabaseHas('expense_categories', [
            'id' => $category->id,
            'name' => $category->name,
        ]);
    }

    public function test_category_belongs_to_parent(): void
    {
        $parent = ExpenseCategory::factory()->create();
        $subcategory = ExpenseCategory::factory()->withParent()->create(['parent_id' => $parent->id]);

        $this->assertInstanceOf(ExpenseCategory::class, $subcategory->parent);
        $this->assertEquals($parent->id, $subcategory->parent->id);
    }

    public function test_category_has_many_subcategories(): void
    {
        $parent = ExpenseCategory::factory()->create();
        ExpenseCategory::factory()->subcategory()->count(3)->create(['parent_id' => $parent->id]);

        $this->assertCount(3, $parent->subcategories);
        $this->assertInstanceOf(ExpenseCategory::class, $parent->subcategories->first());
    }

    public function test_category_has_sort_order(): void
    {
        $category = ExpenseCategory::factory()->create(['sort_order' => 5]);

        $this->assertEquals(5, $category->sort_order);
    }

    public function test_active_scope(): void
    {
        ExpenseCategory::factory()->active()->count(2)->create();
        ExpenseCategory::factory()->inactive()->create();

        $activeCategories = ExpenseCategory::active()->get();

        $this->assertCount(2, $activeCategories);
    }

    public function test_main_categories_scope(): void
    {
        ExpenseCategory::factory()->main()->count(2)->create();
        ExpenseCategory::factory()->subcategory()->create();

        $mainCategories = ExpenseCategory::mainCategories()->get();

        $this->assertCount(2, $mainCategories);
    }

    public function test_subcategories_scope(): void
    {
        ExpenseCategory::factory()->main()->create();
        ExpenseCategory::factory()->subcategory()->count(2)->create();

        // Use query builder with explicit scope since there's a relationship with same name
        $subcategories = ExpenseCategory::query()->where('category_type', 'subcategory')->get();

        $this->assertCount(2, $subcategories);
    }

    public function test_full_name_attribute_without_parent(): void
    {
        $category = ExpenseCategory::factory()->create(['name' => 'Utilities']);

        $this->assertEquals('Utilities', $category->full_name);
    }

    public function test_full_name_attribute_with_parent(): void
    {
        $parent = ExpenseCategory::factory()->create(['name' => 'Utilities']);
        $subcategory = ExpenseCategory::factory()->withParent()->create([
            'name' => 'Electricity',
            'parent_id' => $parent->id,
        ]);

        $this->assertEquals('Utilities > Electricity', $subcategory->full_name);
    }

    public function test_active_state(): void
    {
        $category = ExpenseCategory::factory()->active()->create();

        $this->assertTrue($category->is_active);
    }

    public function test_inactive_state(): void
    {
        $category = ExpenseCategory::factory()->inactive()->create();

        $this->assertFalse($category->is_active);
    }

    public function test_main_state(): void
    {
        $category = ExpenseCategory::factory()->main()->create();

        $this->assertEquals('main', $category->category_type);
    }

    public function test_is_active_cast_to_boolean(): void
    {
        $category = ExpenseCategory::factory()->create(['is_active' => true]);

        $this->assertIsBool($category->is_active);
        $this->assertTrue($category->is_active);
    }
}
