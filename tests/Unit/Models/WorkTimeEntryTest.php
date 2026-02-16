<?php

namespace Tests\Unit\Models;

use App\Models\Instructor;
use App\Models\WorkTimeEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkTimeEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_work_time_entry(): void
    {
        $entry = WorkTimeEntry::factory()->create();
        $this->assertDatabaseHas('work_time_entries', ['id' => $entry->id]);
    }

    public function test_belongs_to_instructor(): void
    {
        $instructor = Instructor::factory()->create();
        $entry = WorkTimeEntry::factory()->create(['instructor_id' => $instructor->id]);

        $this->assertInstanceOf(Instructor::class, $entry->instructor);
        $this->assertEquals($instructor->id, $entry->instructor->id);
    }

    public function test_approved_state(): void
    {
        $entry = WorkTimeEntry::factory()->approved()->create();

        $this->assertTrue($entry->approved);
        $this->assertNotNull($entry->approved_by);
        $this->assertNotNull($entry->approved_at);
    }

    public function test_pending_state(): void
    {
        $entry = WorkTimeEntry::factory()->pending()->create();
        $this->assertFalse($entry->approved);
    }

    public function test_today_state(): void
    {
        $entry = WorkTimeEntry::factory()->today()->create();
        $this->assertEquals(now()->toDateString(), $entry->date->toDateString());
    }

    public function test_has_hours_worked(): void
    {
        $entry = WorkTimeEntry::factory()->create();

        $this->assertNotNull($entry->hours_worked);
        $this->assertGreaterThan(0, $entry->hours_worked);
    }

    public function test_has_start_and_end_time(): void
    {
        $entry = WorkTimeEntry::factory()->create();

        $this->assertNotNull($entry->start_time);
        $this->assertNotNull($entry->end_time);
    }

    public function test_has_description(): void
    {
        $entry = WorkTimeEntry::factory()->create();
        $this->assertNotNull($entry->description);
    }
}
