<?php

namespace Tests\Unit\Models;

use App\Models\ShiftChecklist;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftChecklistTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_shift_checklist(): void
    {
        $checklist = ShiftChecklist::factory()->create();
        $this->assertDatabaseHas('shift_checklists', ['id' => $checklist->id]);
    }

    public function test_belongs_to_store(): void
    {
        $store = Store::factory()->create();
        $checklist = ShiftChecklist::factory()->create(['store_id' => $store->id]);

        $this->assertInstanceOf(Store::class, $checklist->store);
        $this->assertEquals($store->id, $checklist->store->id);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $checklist = ShiftChecklist::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $checklist->user);
        $this->assertEquals($user->id, $checklist->user->id);
    }

    public function test_opening_state(): void
    {
        $checklist = ShiftChecklist::factory()->opening()->create();
        $this->assertEquals('opening', $checklist->type);
    }

    public function test_closing_state(): void
    {
        $checklist = ShiftChecklist::factory()->closing()->create();
        $this->assertEquals('closing', $checklist->type);
        $this->assertEquals('yes', $checklist->doors_locked);
        $this->assertEquals('yes', $checklist->alarm_set);
    }

    public function test_completed_state(): void
    {
        $checklist = ShiftChecklist::factory()->completed()->create();

        $this->assertNotNull($checklist->completed_at);
        $this->assertEquals('yes', $checklist->cash_counted);
        $this->assertEquals('yes', $checklist->towels_checked);
        $this->assertEquals('yes', $checklist->equipment_checked);
    }

    public function test_has_inventory_counts(): void
    {
        $checklist = ShiftChecklist::factory()->create();

        $this->assertNotNull($checklist->towels_count);
        $this->assertNotNull($checklist->water_count);
    }

    public function test_type_is_valid(): void
    {
        $checklist = ShiftChecklist::factory()->create();
        $validTypes = ['opening', 'closing'];

        $this->assertContains($checklist->type, $validTypes);
    }
}
