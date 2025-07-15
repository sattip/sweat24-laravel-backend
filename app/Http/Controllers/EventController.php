<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventRSVP;
use Illuminate\Http\Request;

class EventController extends Controller
{
    // Get all active events
    public function index()
    {
        $events = Event::where('is_active', true)
            ->where('date', '>=', now()->toDateString())
            ->with(['attendees'])
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        $eventsWithAttendeeCount = $events->map(function ($event) {
            return [
                'id' => $event->id,
                'name' => $event->name,
                'date' => $event->date->format('Y-m-d'),
                'time' => \Carbon\Carbon::parse($event->time)->format('H:i'),
                'location' => $event->location,
                'imageUrl' => $event->image_url ?: '/placeholder.svg',
                'description' => $event->description,
                'attendees' => $event->attendees_count,
                'type' => $event->type_display,
                'details' => $event->details,
            ];
        });

        return response()->json($eventsWithAttendeeCount);
    }

    // Create new event
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required|string',
            'location' => 'required|string',
            'image_url' => 'nullable|url',
            'type' => 'required|in:social,educational,fitness,other',
            'details' => 'nullable|array',
            'max_attendees' => 'nullable|integer|min:1',
        ]);

        $event = Event::create($validated);
        
        return response()->json($event, 201);
    }

    // RSVP to an event
    public function rsvp(Request $request, Event $event)
    {
        $validated = $request->validate([
            'response' => 'required|in:yes,no,maybe',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();
        
        // Update or create RSVP
        $rsvp = EventRSVP::updateOrCreate(
            [
                'event_id' => $event->id,
                'user_id' => $user ? $user->id : null,
            ],
            [
                'response' => $validated['response'],
                'notes' => $validated['notes'] ?? null,
                'guest_name' => $user ? null : $request->guest_name,
                'guest_email' => $user ? null : $request->guest_email,
            ]
        );

        // Update event attendee count
        $event->current_attendees = $event->attendees()->count();
        $event->save();

        return response()->json([
            'message' => 'RSVP updated successfully',
            'rsvp' => $rsvp,
            'attendees' => $event->current_attendees,
        ]);
    }

    // Get user's RSVP status for events
    public function getUserRSVPs(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([]);
        }

        $rsvps = EventRSVP::where('user_id', $user->id)
            ->with('event')
            ->get()
            ->keyBy('event_id');

        return response()->json($rsvps);
    }

    // Admin: Get all events (including past ones)
    public function adminIndex()
    {
        $events = Event::with(['rsvps.user'])
            ->orderBy('date', 'desc')
            ->orderBy('time', 'desc')
            ->get();

        return response()->json($events);
    }

    // Admin: Update event
    public function update(Request $request, Event $event)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'date' => 'sometimes|date',
            'time' => 'sometimes|string',
            'location' => 'sometimes|string',
            'image_url' => 'nullable|url',
            'type' => 'sometimes|in:social,educational,fitness,other',
            'details' => 'nullable|array',
            'max_attendees' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $event->update($validated);
        
        return response()->json($event);
    }

    // Admin: Delete event
    public function destroy(Event $event)
    {
        $event->delete();
        return response()->json(['message' => 'Event deleted successfully']);
    }

    // Admin: Get all RSVPs
    public function adminGetAllRsvps()
    {
        $rsvps = EventRSVP::with(['event', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($rsvps);
    }
}
