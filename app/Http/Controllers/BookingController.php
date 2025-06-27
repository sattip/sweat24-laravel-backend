<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Booking::with('user');
        
        if ($request->has('date')) {
            $query->whereDate('date', $request->date);
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('instructor')) {
            $query->where('instructor', 'LIKE', '%' . $request->instructor . '%');
        }
        
        $bookings = $query->orderBy('date')->orderBy('time')->get();
        return response()->json($bookings);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'class_name' => 'required|string|max:255',
            'instructor' => 'required|string|max:255',
            'date' => 'required|date',
            'time' => 'required|string',
            'type' => 'required|string',
            'location' => 'nullable|string|max:255',
        ]);
        
        $validated['status'] = 'confirmed';
        $validated['attended'] = false;
        $validated['booking_time'] = now();
        
        $booking = Booking::create($validated);
        return response()->json($booking->load('user'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Booking $booking)
    {
        return response()->json($booking->load('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'customer_name' => 'sometimes|string|max:255',
            'customer_email' => 'sometimes|email|max:255',
            'class_name' => 'sometimes|string|max:255',
            'instructor' => 'sometimes|string|max:255',
            'date' => 'sometimes|date',
            'time' => 'sometimes|string',
            'status' => 'sometimes|in:confirmed,cancelled,completed,no_show',
            'type' => 'sometimes|string',
            'attended' => 'sometimes|boolean',
            'location' => 'nullable|string|max:255',
            'cancellation_reason' => 'nullable|string',
        ]);
        
        $booking->update($validated);
        return response()->json($booking->load('user'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Booking $booking)
    {
        $booking->delete();
        return response()->json(['message' => 'Booking deleted successfully']);
    }

    /**
     * Check in a booking
     */
    public function checkIn(Booking $booking)
    {
        $booking->update([
            'status' => 'completed',
            'attended' => true,
        ]);
        
        return response()->json($booking->load('user'));
    }

    /**
     * Cancel a booking
     */
    public function cancel(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'cancellation_reason' => 'nullable|string',
        ]);
        
        $booking->update([
            'status' => 'cancelled',
            'cancellation_reason' => $validated['cancellation_reason'] ?? null,
        ]);
        
        return response()->json($booking->load('user'));
    }
}
