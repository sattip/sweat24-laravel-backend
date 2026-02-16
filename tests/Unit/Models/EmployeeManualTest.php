<?php

namespace Tests\Unit\Models;

use App\Models\EmployeeManual;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeManualTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_employee_manual(): void
    {
        $manual = EmployeeManual::factory()->create();
        $this->assertDatabaseHas('employee_manual', ['id' => $manual->id]);
    }

    public function test_active_state(): void
    {
        $manual = EmployeeManual::factory()->active()->create();
        $this->assertTrue($manual->is_active);
    }

    public function test_inactive_state(): void
    {
        $manual = EmployeeManual::factory()->inactive()->create();
        $this->assertFalse($manual->is_active);
    }

    public function test_general_category_state(): void
    {
        $manual = EmployeeManual::factory()->general()->create();
        $this->assertEquals('general', $manual->category);
    }

    public function test_procedures_state(): void
    {
        $manual = EmployeeManual::factory()->procedures()->create();
        $this->assertEquals('procedures', $manual->category);
    }

    public function test_safety_state(): void
    {
        $manual = EmployeeManual::factory()->safety()->create();
        $this->assertEquals('safety', $manual->category);
    }

    public function test_category_is_valid(): void
    {
        $manual = EmployeeManual::factory()->create();
        $validCategories = ['general', 'rules', 'procedures', 'safety', 'customer_service', 'equipment', 'faq'];

        $this->assertContains($manual->category, $validCategories);
    }

    public function test_category_label_attribute(): void
    {
        $manual = EmployeeManual::factory()->general()->create();
        $this->assertEquals('Γενικές Πληροφορίες', $manual->category_label);
    }

    public function test_has_title_and_content(): void
    {
        $manual = EmployeeManual::factory()->create();

        $this->assertNotEmpty($manual->title);
        $this->assertNotEmpty($manual->content);
    }

    public function test_order_is_set(): void
    {
        $manual = EmployeeManual::factory()->create();
        $this->assertNotNull($manual->order);
    }
}
