<?php

namespace App\Http\Controllers;

use App\Models\CancellationPolicy;
use App\Models\Booking;
use App\Models\BookingReschedule;
use App\Models\GymClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CancellationPolicyController extends Controller
{
    /**
     * Display a listing of the policies.
     */
    public function index()
    {
        $policies = CancellationPolicy::active()->get();
        return response()->json($policies);
    }

    /**
     * Store a newly created policy.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'hours_before' => 'required|integer|min:0',
            'penalty_percentage' => 'required|numeric|min:0|max:100',
            'allow_reschedule' => 'required|boolean',
            'reschedule_hours_before' => 'nullable|integer|min:0',
            'max_reschedules_per_month' => 'required|integer|min:0',
            'priority' => 'integer',
            'applicable_to' => 'nullable|array',
        ]);

        $policy = CancellationPolicy::create($validated);
        return response()->json($policy, 201);
    }

    /**
     * Display the specified policy.
     */
    public function show(CancellationPolicy $cancellationPolicy)
    {
        return response()->json($cancellationPolicy);
    }

    /**
     * Update the specified policy.
     */
    public function update(Request $request, CancellationPolicy $cancellationPolicy)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'hours_before' => 'sometimes|integer|min:0',
            'penalty_percentage' => 'sometimes|numeric|min:0|max:100',
            'allow_reschedule' => 'sometimes|boolean',
            'reschedule_hours_before' => 'nullable|integer|min:0',
            'max_reschedules_per_month' => 'sometimes|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'priority' => 'sometimes|integer',
            'applicable_to' => 'nullable|array',
        ]);

        $cancellationPolicy->update($validated);
        return response()->json($cancellationPolicy);
    }

    /**
     * Remove the specified policy.
     */
    public function destroy(CancellationPolicy $cancellationPolicy)
    {
        $cancellationPolicy->delete();
        return response()->json(['message' => 'Policy deleted successfully']);
    }

    /**
     * Test policy endpoint for client app
     * GET /api/v1/test-policy/{booking_id}
     */
    public function testPolicy($bookingId)
    {
        try {
            $booking = Booking::find($bookingId);
            
            if (!$booking) {
                return response()->json([
                    'error' => 'Booking not found'
                ], 404);
            }
            
            // Calculate hours until class - handle both date formats
            try {
                // Try parsing as combined datetime first
                if (strlen($booking->time) > 5) {
                    $classDateTime = Carbon::parse($booking->time);
                } else {
                    // Parse date and time separately
                    $classDateTime = Carbon::parse($booking->date)->setTimeFromTimeString($booking->time);
                }
            } catch (\Exception $e) {
                // Fallback: assume class is tomorrow at given time
                $classDateTime = Carbon::tomorrow()->setTimeFromTimeString($booking->time ?: '12:00');
            }
            
            $now = Carbon::now();
            $hoursUntilClass = $now->diffInHours($classDateTime, false);
            
            // Policy logic - CORRECT RULES (no penalty logic)
            $canCancel = $hoursUntilClass >= 3; // Ακύρωση: έως 3 ώρες πριν
            $canReschedule = $hoursUntilClass >= 6; // Αλλαγή ώρας: έως 6 ώρες πριν  
            $canCancelWithoutPenalty = true; // No penalty system implemented
            $penaltyPercentage = 0; // No penalties
            
            return response()->json([
                'can_cancel' => $canCancel,
                'can_reschedule' => $canReschedule,
                'can_cancel_without_penalty' => $canCancelWithoutPenalty,
                'penalty_percentage' => $penaltyPercentage,
                'hours_until_class' => round($hoursUntilClass, 1),
                'policy' => [
                    'name' => 'Βασική Πολιτική',
                    'description' => 'Βασική πολιτική ακύρωσης και μετάθεσης'
                ],
                'booking_info' => [
                    'id' => $booking->id,
                    'class_name' => $booking->class_name,
                    'date' => $booking->date,
                    'time' => $booking->time,
                    'instructor' => $booking->instructor,
                    'status' => $booking->status
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Check if booking can be cancelled/rescheduled
     */
    public function checkBookingPolicy(Request $request, Booking $booking)
    {
        return $this->performPolicyCheck($booking);
    }
    
    /**
     * Perform the actual policy check logic
     */
    private function performPolicyCheck(Booking $booking)
    {
        if (!$booking->class_id) {
            return response()->json([
                'can_cancel' => true,
                'can_reschedule' => true,
                'penalty_percentage' => 0,
                'message' => 'Η κράτηση δεν συνδέεται με συγκεκριμένο μάθημα'
            ]);
        }

        $class = GymClass::find($booking->class_id);
        if (!$class) {
            return response()->json(['error' => 'Class not found'], 404);
        }

        // Calculate hours until class
        $classDateTime = Carbon::parse($class->date->format('Y-m-d') . ' ' . $class->time);
        $hoursUntilClass = now()->diffInHours($classDateTime, false);

        // Find applicable policy
        $policies = CancellationPolicy::active()->get();
        $applicablePolicy = null;

        foreach ($policies as $policy) {
            if ($policy->appliesToClassType($class->type)) {
                $applicablePolicy = $policy;
                break;
            }
        }

        if (!$applicablePolicy) {
            // Default policy: 6 hours for cancellation, 3 hours for reschedule
            return response()->json([
                'can_cancel' => $hoursUntilClass >= 6,
                'can_reschedule' => $hoursUntilClass >= 3,
                'penalty_percentage' => 0,
                'hours_until_class' => max(0, $hoursUntilClass),
                'message' => 'Χρησιμοποιείται η προεπιλεγμένη πολιτική ακύρωσης'
            ]);
        }

        // Check reschedule count for the month
        $rescheduleCount = BookingReschedule::getRescheduleCountForMonth($booking->user_id);
        $canReschedule = $applicablePolicy->canReschedule($hoursUntilClass) && 
                        $rescheduleCount < $applicablePolicy->max_reschedules_per_month;

        return response()->json([
            'can_cancel' => $hoursUntilClass > 0,
            'can_cancel_without_penalty' => $applicablePolicy->canCancelWithoutPenalty($hoursUntilClass),
            'can_reschedule' => $canReschedule,
            'penalty_percentage' => $applicablePolicy->canCancelWithoutPenalty($hoursUntilClass) ? 0 : $applicablePolicy->penalty_percentage,
            'hours_until_class' => max(0, $hoursUntilClass),
            'policy' => [
                'name' => $applicablePolicy->name,
                'description' => $applicablePolicy->description,
                'hours_before' => $applicablePolicy->hours_before,
                'reschedules_used' => $rescheduleCount,
                'reschedules_allowed' => $applicablePolicy->max_reschedules_per_month,
            ]
        ]);
    }

    /**
     * Request reschedule for a booking
     */
    public function requestReschedule(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'new_class_id' => 'required|exists:gym_classes,id',
            'reason' => 'nullable|string|max:500',
        ]);

        // Check if can reschedule
        $policyCheck = $this->checkBookingPolicy($request, $booking);
        $policyData = $policyCheck->getData();

        if (!$policyData->can_reschedule) {
            return response()->json([
                'success' => false,
                'message' => 'Δεν μπορείτε να μεταθέσετε αυτή την κράτηση'
            ], 400);
        }

        $originalClass = GymClass::find($booking->class_id);
        $newClass = GymClass::find($validated['new_class_id']);

        // Check if new class has space
        if ($newClass->isFull()) {
            return response()->json([
                'success' => false,
                'message' => 'Το νέο μάθημα είναι πλήρες'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Create reschedule request
            $reschedule = BookingReschedule::create([
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id ?? auth()->id(),
                'original_class_id' => $booking->class_id,
                'new_class_id' => $validated['new_class_id'],
                'original_datetime' => Carbon::parse($originalClass->date->format('Y-m-d') . ' ' . $originalClass->time),
                'new_datetime' => Carbon::parse($newClass->date->format('Y-m-d') . ' ' . $newClass->time),
                'reason' => $validated['reason'],
                'status' => 'pending',
                'requested_at' => now(),
            ]);

            // Auto-approve if within policy limits
            if ($policyData->can_reschedule && $policyData->hours_until_class > 24) {
                $reschedule->update([
                    'status' => 'approved',
                    'processed_at' => now(),
                    'processed_by' => auth()->id(),
                ]);

                // Update booking
                $booking->update([
                    'class_id' => $validated['new_class_id'],
                    'date' => $newClass->date,
                    'time' => $newClass->time,
                ]);

                // Update class participants
                $originalClass->decrement('current_participants');
                $newClass->increment('current_participants');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $reschedule->status === 'approved' 
                    ? 'Η μετάθεση εγκρίθηκε αυτόματα' 
                    : 'Το αίτημα μετάθεσης καταχωρήθηκε',
                'reschedule' => $reschedule->load(['originalClass', 'newClass']),
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Σφάλμα κατά την καταχώρηση του αιτήματος'
            ], 500);
        }
    }

    /**
     * Get user's reschedule history
     */
    public function userRescheduleHistory(Request $request)
    {
        $userId = auth()->id();
        $reschedules = BookingReschedule::with(['originalClass', 'newClass', 'booking'])
            ->forUser($userId)
            ->orderBy('requested_at', 'desc')
            ->paginate(20);

        return response()->json($reschedules);
    }

    /**
     * Admin: Get all reschedule requests
     */
    public function adminRescheduleRequests(Request $request)
    {
        $query = BookingReschedule::with(['user', 'originalClass', 'newClass', 'booking']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $reschedules = $query->orderBy('requested_at', 'desc')->paginate(20);

        return response()->json($reschedules);
    }

    /**
     * Admin: Process reschedule request
     */
    public function processReschedule(Request $request, BookingReschedule $reschedule)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'admin_notes' => 'nullable|string',
        ]);

        if ($reschedule->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Το αίτημα έχει ήδη επεξεργαστεί'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $reschedule->update([
                'status' => $validated['status'],
                'processed_at' => now(),
                'processed_by' => auth()->id(),
                'admin_notes' => $validated['admin_notes'],
            ]);

            if ($validated['status'] === 'approved') {
                $booking = $reschedule->booking;
                $originalClass = $reschedule->originalClass;
                $newClass = $reschedule->newClass;

                // Update booking
                $booking->update([
                    'class_id' => $reschedule->new_class_id,
                    'date' => $newClass->date,
                    'time' => $newClass->time,
                ]);

                // Update class participants
                $originalClass->decrement('current_participants');
                $newClass->increment('current_participants');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $validated['status'] === 'approved' 
                    ? 'Η μετάθεση εγκρίθηκε' 
                    : 'Η μετάθεση απορρίφθηκε',
                'reschedule' => $reschedule->fresh()->load(['user', 'originalClass', 'newClass']),
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Σφάλμα κατά την επεξεργασία του αιτήματος'
            ], 500);
        }
    }
}