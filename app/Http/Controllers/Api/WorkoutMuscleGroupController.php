<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\WorkoutMuscleGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkoutMuscleGroupController extends Controller
{
    /**
     * Store or update muscle groups for a workout
     */
    public function store(Request $request, $bookingId)
    {
        $validated = $request->validate([
            'muscle_groups' => [
                'required',
                'array',
                'min:1',
            ],
            'muscle_groups.*' => [
                'required',
                'string',
                'in:' . implode(',', WorkoutMuscleGroup::VALID_MUSCLE_GROUPS),
            ],
        ]);

        $booking = Booking::findOrFail($bookingId);

        if ($booking->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this booking',
            ], 403);
        }

        if (!$booking->attended) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot record muscle groups for a workout you did not attend',
            ], 422);
        }

        $muscleGroups = WorkoutMuscleGroup::updateOrCreate(
            [
                'booking_id' => $bookingId,
                'user_id' => Auth::id(),
            ],
            [
                'muscle_groups' => $validated['muscle_groups'],
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Muscle groups saved successfully',
            'data' => $muscleGroups,
        ], 200);
    }

    /**
     * Get muscle groups for a specific workout
     */
    public function show($bookingId)
    {
        $booking = Booking::findOrFail($bookingId);

        if ($booking->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this booking',
            ], 403);
        }

        $muscleGroups = WorkoutMuscleGroup::where('booking_id', $bookingId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$muscleGroups) {
            return response()->json([
                'success' => false,
                'message' => 'No muscle groups recorded for this workout',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $muscleGroups,
        ], 200);
    }
}