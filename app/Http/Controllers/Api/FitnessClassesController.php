<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FitnessClass;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class FitnessClassesController extends Controller
{
    /**
     * Display a listing of fitness classes
     */
    public function index(Request $request): JsonResponse
    {
        $query = FitnessClass::with('store:id,name,color');

        // Filter by date
        if ($request->has('date')) {
            $query->byDate($request->date);
        }

        // Filter by date range
        if ($request->has('date_from') || $request->has('date_to')) {
            if ($request->date_from) {
                $query->where('date', '>=', $request->date_from);
            }
            if ($request->date_to) {
                $query->where('date', '<=', $request->date_to);
            }
        }

        // Filter by instructor
        if ($request->has('instructor')) {
            $query->byInstructor($request->instructor);
        }

        // Filter by store
        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Order by date and time
        $classes = $query->orderBy('date', 'asc')
                         ->orderBy('time', 'asc')
                         ->get();

        // Transform data to include frontend-expected fields
        $transformedClasses = $classes->map(function ($class) {
            $startTime = Carbon::parse($class->time);
            $endTime = $startTime->copy()->addMinutes($class->duration);

            return [
                'id' => $class->id,
                'name' => $class->name,
                'type' => $class->type,
                'class_type' => $class->name, // Alias for frontend
                'instructor' => $class->instructor,
                'trainer_name' => $class->instructor, // Alias for frontend
                'trainer_id' => null, // Not using numeric IDs yet
                'date' => $class->date ? $class->date->format('Y-m-d') : null,
                'time' => $startTime->format('H:i'),
                'start_time' => $startTime->format('H:i'),
                'end_time' => $endTime->format('H:i'),
                'duration' => $class->duration,
                'max_participants' => $class->max_participants,
                'maxParticipants' => $class->max_participants, // Alias
                'current_participants' => $class->current_participants,
                'currentParticipants' => $class->current_participants, // Alias
                'priority_seats' => $class->priority_seats ?? 0,
                'store_id' => $class->store_id,
                'store_name' => $class->store?->name,
                'location' => $class->location,
                'description' => $class->description,
                'status' => $class->status,
                'is_recurring' => $class->is_recurring,
                'store' => $class->store,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $transformedClasses
        ]);
    }

    /**
     * Store a newly created fitness class
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'instructor' => 'required|string|max:255',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
            'duration' => 'required|integer|min:15|max:480',
            'max_participants' => 'required|integer|min:1|max:100',
            'store_id' => 'required|exists:stores,id',
            'location' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:1000',
            'status' => 'in:active,cancelled,completed',
            'is_recurring' => 'boolean',
            'recurrence_pattern' => 'nullable|in:daily,weekly,monthly',
            'recurrence_interval' => 'nullable|integer|min:1|max:52',
            'recurrence_end_date' => 'nullable|date|after:date'
        ]);

        $class = FitnessClass::create($validated);
        $class->load('store:id,name,color');

        // Transform for frontend
        $startTime = Carbon::parse($class->time);
        $endTime = $startTime->copy()->addMinutes($class->duration);

        $transformedClass = [
            'id' => $class->id,
            'name' => $class->name,
            'type' => $class->type,
            'class_type' => $class->name,
            'instructor' => $class->instructor,
            'trainer_name' => $class->instructor,
            'trainer_id' => null,
            'date' => $class->date ? $class->date->format('Y-m-d') : null,
            'time' => $startTime->format('H:i'),
            'start_time' => $startTime->format('H:i'),
            'end_time' => $endTime->format('H:i'),
            'duration' => $class->duration,
            'max_participants' => $class->max_participants,
            'maxParticipants' => $class->max_participants,
            'current_participants' => $class->current_participants,
            'currentParticipants' => $class->current_participants,
            'priority_seats' => $class->priority_seats ?? 0,
            'store_id' => $class->store_id,
            'store_name' => $class->store?->name,
            'location' => $class->location,
            'description' => $class->description,
            'status' => $class->status,
            'is_recurring' => $class->is_recurring,
            'store' => $class->store,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Το μάθημα δημιουργήθηκε επιτυχώς',
            'data' => $transformedClass
        ], 201);
    }

    /**
     * Display the specified fitness class
     */
    public function show($id): JsonResponse
    {
        $class = FitnessClass::with('store:id,name,color', 'bookings')->findOrFail($id);

        // Transform for frontend
        $startTime = Carbon::parse($class->time);
        $endTime = $startTime->copy()->addMinutes($class->duration);

        $transformedClass = [
            'id' => $class->id,
            'name' => $class->name,
            'type' => $class->type,
            'class_type' => $class->name,
            'instructor' => $class->instructor,
            'trainer_name' => $class->instructor,
            'trainer_id' => null,
            'date' => $class->date ? $class->date->format('Y-m-d') : null,
            'time' => $startTime->format('H:i'),
            'start_time' => $startTime->format('H:i'),
            'end_time' => $endTime->format('H:i'),
            'duration' => $class->duration,
            'max_participants' => $class->max_participants,
            'maxParticipants' => $class->max_participants,
            'current_participants' => $class->current_participants,
            'currentParticipants' => $class->current_participants,
            'priority_seats' => $class->priority_seats ?? 0,
            'store_id' => $class->store_id,
            'store_name' => $class->store?->name,
            'location' => $class->location,
            'description' => $class->description,
            'status' => $class->status,
            'is_recurring' => $class->is_recurring,
            'store' => $class->store,
            'bookings' => $class->bookings,
        ];

        return response()->json([
            'success' => true,
            'data' => $transformedClass
        ]);
    }

    /**
     * Update the specified fitness class
     */
    public function update(Request $request, $id): JsonResponse
    {
        $class = FitnessClass::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'instructor' => 'required|string|max:255',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
            'duration' => 'required|integer|min:15|max:480',
            'max_participants' => 'required|integer|min:1|max:100',
            'store_id' => 'nullable|exists:stores,id',
            'location' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:1000',
            'status' => 'in:active,cancelled,completed'
        ]);

        $class->update($validated);
        $class->load('store:id,name,color');

        // Transform for frontend
        $startTime = Carbon::parse($class->time);
        $endTime = $startTime->copy()->addMinutes($class->duration);

        $transformedClass = [
            'id' => $class->id,
            'name' => $class->name,
            'type' => $class->type,
            'class_type' => $class->name,
            'instructor' => $class->instructor,
            'trainer_name' => $class->instructor,
            'trainer_id' => null,
            'date' => $class->date ? $class->date->format('Y-m-d') : null,
            'time' => $startTime->format('H:i'),
            'start_time' => $startTime->format('H:i'),
            'end_time' => $endTime->format('H:i'),
            'duration' => $class->duration,
            'max_participants' => $class->max_participants,
            'maxParticipants' => $class->max_participants,
            'current_participants' => $class->current_participants,
            'currentParticipants' => $class->current_participants,
            'priority_seats' => $class->priority_seats ?? 0,
            'store_id' => $class->store_id,
            'store_name' => $class->store?->name,
            'location' => $class->location,
            'description' => $class->description,
            'status' => $class->status,
            'is_recurring' => $class->is_recurring,
            'store' => $class->store,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Το μάθημα ενημερώθηκε επιτυχώς',
            'data' => $transformedClass
        ]);
    }

    /**
     * Remove the specified fitness class
     */
    public function destroy($id): JsonResponse
    {
        $class = FitnessClass::findOrFail($id);
        // Check if class has bookings
        if ($class->bookings()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Δεν μπορείτε να διαγράψετε αυτό το μάθημα γιατί έχει κρατήσεις'
            ], 422);
        }

        $class->delete();

        return response()->json([
            'success' => true,
            'message' => 'Το μάθημα διαγράφηκε επιτυχώς'
        ]);
    }

    /**
     * Get available stores for dropdown
     */
    public function getAvailableStores(): JsonResponse
    {
        $stores = Store::active()->select('id', 'name', 'color')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $stores
        ]);
    }

    /**
     * Get class statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $query = FitnessClass::query();

        // Filter by date range if provided
        if ($request->has('date_from') || $request->has('date_to')) {
            if ($request->date_from) {
                $query->where('date', '>=', $request->date_from);
            }
            if ($request->date_to) {
                $query->where('date', '<=', $request->date_to);
            }
        }

        $stats = [
            'total_classes' => $query->count(),
            'active_classes' => (clone $query)->where('status', 'active')->count(),
            'cancelled_classes' => (clone $query)->where('status', 'cancelled')->count(),
            'completed_classes' => (clone $query)->where('status', 'completed')->count(),
            'total_participants' => (clone $query)->sum('current_participants'),
            'average_participants' => round((clone $query)->avg('current_participants') ?? 0, 1),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get distinct class types
     */
    public function getTypes(): JsonResponse
    {
        $types = \App\Models\ClassType::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function ($classType) {
                return [
                    'type' => $classType->value,
                    'label' => $classType->name,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $types
        ]);
    }

    /**
     * Get participants for a fitness class
     */
    public function getParticipants($id): JsonResponse
    {
        $class = FitnessClass::findOrFail($id);

        // Get bookings for this class that are not cancelled
        $bookings = \App\Models\Booking::where('class_id', $id)
            ->whereIn('status', ['confirmed', 'pending', 'completed', 'absent'])
            ->with('user:id,name,email,phone')
            ->get();

        $participants = $bookings->map(function ($booking) {
            // Try to get user package info
            $userPackage = null;
            if ($booking->user_id) {
                $userPackage = \App\Models\UserPackage::where('user_id', $booking->user_id)
                    ->where('status', 'active')
                    ->first();
            }

            return [
                'id' => $booking->id,
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'user_name' => $booking->customer_name ?? $booking->user?->name ?? 'Άγνωστος',
                'user_email' => $booking->customer_email ?? $booking->user?->email ?? '',
                'user_phone' => $booking->user?->phone ?? null,
                'status' => $booking->status,
                'checked_in' => $booking->status === 'completed' || $booking->attended,
                'package_name' => $userPackage?->package?->name ?? null,
                'remaining_sessions' => $userPackage?->remaining_sessions ?? null,
            ];
        });

        return response()->json([
            'success' => true,
            'participants' => $participants
        ]);
    }

    /**
     * Mark attendance for a participant in a fitness class
     */
    public function markAttendance(Request $request, $id): JsonResponse
    {
        $class = FitnessClass::findOrFail($id);

        $validated = $request->validate([
            'booking_id' => 'required|integer|exists:bookings,id',
            'status' => 'required|in:present,absent',
            'with_charge' => 'boolean',
        ]);

        $booking = \App\Models\Booking::findOrFail($validated['booking_id']);

        // Verify booking belongs to this class
        if ($booking->class_id != $id) {
            return response()->json([
                'success' => false,
                'message' => 'Η κράτηση δεν ανήκει σε αυτό το μάθημα'
            ], 422);
        }

        if ($validated['status'] === 'present') {
            // Mark as present (completed)
            $booking->update([
                'status' => 'completed',
                'attended' => true,
            ]);

            // Deduct session from user package if applicable
            if ($booking->user_id) {
                $userPackage = \App\Models\UserPackage::where('user_id', $booking->user_id)
                    ->where('status', 'active')
                    ->where('remaining_sessions', '>', 0)
                    ->first();

                if ($userPackage) {
                    $userPackage->decrement('remaining_sessions');

                    // Check if package is now empty
                    if ($userPackage->remaining_sessions <= 0) {
                        $userPackage->update(['status' => 'completed']);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Η παρουσία καταχωρήθηκε επιτυχώς'
            ]);
        } else {
            // Mark as absent
            $withCharge = $validated['with_charge'] ?? true;

            $booking->update([
                'status' => 'absent',
                'attended' => false,
                'absence_reason' => 'Απουσία από ομαδικό μάθημα',
                'absence_with_charge' => $withCharge,
                'absence_marked_at' => now(),
                'absence_marked_by' => auth()->id(),
            ]);

            // If charging for absence, deduct from package
            if ($withCharge && $booking->user_id) {
                $userPackage = \App\Models\UserPackage::where('user_id', $booking->user_id)
                    ->where('status', 'active')
                    ->where('remaining_sessions', '>', 0)
                    ->first();

                if ($userPackage) {
                    $userPackage->decrement('remaining_sessions');

                    if ($userPackage->remaining_sessions <= 0) {
                        $userPackage->update(['status' => 'completed']);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => $withCharge
                    ? 'Η απουσία καταχωρήθηκε με χρέωση'
                    : 'Η απουσία καταχωρήθηκε χωρίς χρέωση'
            ]);
        }
    }
}
