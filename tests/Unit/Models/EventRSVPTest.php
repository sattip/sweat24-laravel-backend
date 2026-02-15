<?php

namespace Tests\Unit\Models;

use App\Models\Event;
use App\Models\EventRSVP;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventRSVPTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_event_rsvp(): void
    {
        $event = Event::factory()->create();
        $user = User::factory()->create();

        $rsvp = EventRSVP::factory()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('event_r_s_v_p_s', [
            'id' => $rsvp->id,
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_rsvp_belongs_to_event(): void
    {
        $event = Event::factory()->create();
        $rsvp = EventRSVP::factory()->create(['event_id' => $event->id]);

        $this->assertInstanceOf(Event::class, $rsvp->event);
        $this->assertEquals($event->id, $rsvp->event->id);
    }

    public function test_rsvp_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $rsvp = EventRSVP::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $rsvp->user);
        $this->assertEquals($user->id, $rsvp->user->id);
    }

    public function test_attending_state(): void
    {
        $rsvp = EventRSVP::factory()->attending()->create();

        $this->assertEquals('yes', $rsvp->response);
    }

    public function test_not_attending_state(): void
    {
        $rsvp = EventRSVP::factory()->notAttending()->create();

        $this->assertEquals('no', $rsvp->response);
    }

    public function test_maybe_state(): void
    {
        $rsvp = EventRSVP::factory()->maybe()->create();

        $this->assertEquals('maybe', $rsvp->response);
    }

    public function test_as_guest_state(): void
    {
        $rsvp = EventRSVP::factory()->asGuest()->create();

        $this->assertNull($rsvp->user_id);
        $this->assertNotNull($rsvp->guest_name);
        $this->assertNotNull($rsvp->guest_email);
    }

    public function test_rsvp_can_have_notes(): void
    {
        $rsvp = EventRSVP::factory()->create(['notes' => 'Looking forward to it!']);

        $this->assertEquals('Looking forward to it!', $rsvp->notes);
    }
}
