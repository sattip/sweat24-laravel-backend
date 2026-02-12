<?php

namespace Tests\Unit\Models;

use App\Models\Event;
use App\Models\EventRSVP;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_event(): void
    {
        $event = Event::factory()->create();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'name' => $event->name,
        ]);
    }

    public function test_event_has_many_rsvps(): void
    {
        $event = Event::factory()->create();
        EventRSVP::factory()->count(3)->create(['event_id' => $event->id]);

        $this->assertCount(3, $event->rsvps);
        $this->assertInstanceOf(EventRSVP::class, $event->rsvps->first());
    }

    public function test_attendees_returns_only_yes_responses(): void
    {
        $event = Event::factory()->create();
        EventRSVP::factory()->attending()->count(2)->create(['event_id' => $event->id]);
        EventRSVP::factory()->notAttending()->create(['event_id' => $event->id]);
        EventRSVP::factory()->maybe()->create(['event_id' => $event->id]);

        $this->assertCount(2, $event->attendees);
    }

    public function test_attendees_count_attribute(): void
    {
        $event = Event::factory()->create();
        EventRSVP::factory()->attending()->count(3)->create(['event_id' => $event->id]);
        EventRSVP::factory()->notAttending()->create(['event_id' => $event->id]);

        $this->assertEquals(3, $event->attendees_count);
    }

    public function test_type_display_attribute(): void
    {
        $event = Event::factory()->social()->create();

        $this->assertEquals('Κοινωνική Εκδήλωση', $event->type_display);
    }

    public function test_type_display_attribute_educational(): void
    {
        $event = Event::factory()->educational()->create();

        $this->assertEquals('Εκπαιδευτικό', $event->type_display);
    }

    public function test_type_display_attribute_fitness(): void
    {
        $event = Event::factory()->fitness()->create();

        $this->assertEquals('Fitness', $event->type_display);
    }

    public function test_active_state(): void
    {
        $event = Event::factory()->active()->create();

        $this->assertTrue($event->is_active);
    }

    public function test_inactive_state(): void
    {
        $event = Event::factory()->inactive()->create();

        $this->assertFalse($event->is_active);
    }

    public function test_past_state(): void
    {
        $event = Event::factory()->past()->create();

        $this->assertTrue($event->date->isPast());
    }

    public function test_upcoming_state(): void
    {
        $event = Event::factory()->upcoming()->create();

        $this->assertTrue($event->date->isFuture());
    }

    public function test_full_state(): void
    {
        $event = Event::factory()->state(['max_attendees' => 50])->full()->create();

        $this->assertEquals(50, $event->current_attendees);
        $this->assertEquals($event->max_attendees, $event->current_attendees);
    }

    public function test_date_cast_to_date(): void
    {
        $event = Event::factory()->create(['date' => '2026-02-15']);

        $this->assertInstanceOf(\Carbon\Carbon::class, $event->date);
        $this->assertEquals('2026-02-15', $event->date->format('Y-m-d'));
    }

    public function test_is_active_cast_to_boolean(): void
    {
        $event = Event::factory()->create(['is_active' => true]);

        $this->assertIsBool($event->is_active);
        $this->assertTrue($event->is_active);
    }

    public function test_details_cast_to_json(): void
    {
        $event = Event::factory()->create(['details' => ['key' => 'value']]);

        $this->assertIsArray($event->details);
        $this->assertEquals('value', $event->details['key']);
    }
}
