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
        $policies = CancellationPolicy::orderBy('priority', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json([
            'data' => $policies,
            'meta' => [
                'total' => $policies->count(),
                'active' => $policies->where('is_active', true)->count(),
                'inactive' => $policies->where('is_active', false)->count()
            ]
        ]);
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
            'priority' => 'sometimes|integer|min:1',
            'applicable_to' => 'nullable|array',
            'applicable_to.class_types' => 'nullable|array',
            'applicable_to.package_ids' => 'nullable|array'
        ]);

        // Auto-assign priority if not provided
        if (!isset($validated['priority'])) {
            $maxPriority = CancellationPolicy::max('priority') ?? 0;
            $validated['priority'] = $maxPriority + 1;
        }

        // Validate reschedule fields if reschedule is allowed
        if ($validated['allow_reschedule'] && !$validated['reschedule_hours_before']) {
            $validated['reschedule_hours_before'] = $validated['hours_before'];
        }

        $policy = CancellationPolicy::create($validated);
        
        return response()->json([
            'data' => $policy,
            'message' => 'Η πολιτική ακύρωσης δημιουργήθηκε επιτυχώς'
        ], 201);
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
            'priority' => 'sometimes|integer|min:1',
            'applicable_to' => 'nullable|array',
            'applicable_to.class_types' => 'nullable|array',
            'applicable_to.package_ids' => 'nullable|array'
        ]);

        // Handle priority uniqueness
        if (isset($validated['priority']) && $validated['priority'] != $cancellationPolicy->priority) {
            $existingWithPriority = CancellationPolicy::where('priority', $validated['priority'])
                ->where('id', '!=', $cancellationPolicy->id)
                ->first();
            
            if ($existingWithPriority) {
                return response()->json([
                    'error' => 'Validation failed',
                    'details' => ['priority' => ['Η προτεραιότητα πρέπει να είναι μοναδική']]
                ], 422);
            }
        }

        // Validate reschedule fields if reschedule is being enabled
        if (isset($validated['allow_reschedule']) && $validated['allow_reschedule'] && 
            !isset($validated['reschedule_hours_before']) && !$cancellationPolicy->reschedule_hours_before) {
            $validated['reschedule_hours_before'] = $validated['hours_before'] ?? $cancellationPolicy->hours_before;
        }

        $cancellationPolicy->update($validated);
        
        return response()->json([
            'data' => $cancellationPolicy->fresh(),
            'message' => 'Η πολιτική ακύρωσης ενημερώθηκε επιτυχώς'
        ]);
    }

    /**
     * Remove the specified policy.
     */
    public function destroy(CancellationPolicy $cancellationPolicy)
    {
        // Check if policy is being used by any classes or bookings
        $usageCount = DB::table('gym_classes')
            ->where('cancellation_policy_id', $cancellationPolicy->id)
            ->count();
            
        if ($usageCount > 0) {
            return response()->json([
                'error' => 'Δεν είναι δυνατή η διαγραφή',
                'message' => "Η πολιτική χρησιμοποιείται από {$usageCount} μαθήματα"
            ], 422);
        }

        $cancellationPolicy->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Η πολιτική ακύρωσης διαγράφηκε επιτυχώς'
        ]);
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
            
            // Get dynamic policy based on class
            $class = null;
            if ($booking->class_id) {
                $class = GymClass::find($booking->class_id);
            }
            
            $applicablePolicy = $class ? $class->getApplicablePolicy() : null;
            
            if ($applicablePolicy) {
                // Use dynamic policy
                $canCancel = $hoursUntilClass >= $applicablePolicy->hours_before;
                $canCancelWithoutPenalty = $applicablePolicy->canCancelWithoutPenalty($hoursUntilClass);
                $canReschedule = $applicablePolicy->canReschedule($hoursUntilClass);
                $penaltyPercentage = $canCancelWithoutPenalty ? 0 : $applicablePolicy->penalty_percentage;
                
                $policyInfo = [
                    'name' => $applicablePolicy->name,
                    'description' => $applicablePolicy->description,
                    'hours_before' => $applicablePolicy->hours_before,
                    'penalty_percentage' => $applicablePolicy->penalty_percentage,
                    'allow_reschedule' => $applicablePolicy->allow_reschedule,
                    'reschedule_hours_before' => $applicablePolicy->reschedule_hours_before
                ];
            } else {
                // Fallback to default policy
                $canCancel = $hoursUntilClass >= 6;
                $canReschedule = $hoursUntilClass >= 3;  
                $canCancelWithoutPenalty = true;
                $penaltyPercentage = 0;
                
                $policyInfo = [
                    'name' => 'Βασική Πολιτική',
                    'description' => 'Βασική πολιτική ακύρωσης και μετάθεσης'
                ];
            }
            
            return response()->json([
                'can_cancel' => $canCancel,
                'can_reschedule' => $canReschedule,
                'can_cancel_without_penalty' => $canCancelWithoutPenalty,
                'penalty_percentage' => $penaltyPercentage,
                'hours_until_class' => round($hoursUntilClass, 1),
                'policy' => $policyInfo,
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
            // Default policy: 6 hours for cancellation, 3 hours for reschedule (FINAL CORRECT RULES)
            return response()->json([
                'can_cancel' => $hoursUntilClass >= 6,  // CORRECT: Ακύρωση έως 6+ ώρες πριν
                'can_reschedule' => $hoursUntilClass >= 3, // CORRECT: Μετάθεση έως 3+ ώρες πριν  
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

                // Update class participants - CALCULATE FROM LIVE DATA (NO MORE NEGATIVE COUNTS!)
                $originalActualCount = Booking::where('class_id', $booking->class_id)
                    ->whereNotIn('status', ['cancelled'])
                    ->count();
                $newActualCount = Booking::where('class_id', $validated['new_class_id'])
                    ->whereNotIn('status', ['cancelled'])
                    ->count();
                
                $originalClass->update(['current_participants' => $originalActualCount]);
                $newClass->update(['current_participants' => $newActualCount]);
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

                // Update class participants - CALCULATE FROM LIVE DATA (NO MORE NEGATIVE COUNTS!)
                $originalActualCount = Booking::where('class_id', $reschedule->original_class_id)
                    ->whereNotIn('status', ['cancelled'])
                    ->count();
                $newActualCount = Booking::where('class_id', $reschedule->new_class_id)
                    ->whereNotIn('status', ['cancelled'])
                    ->count();
                
                $originalClass->update(['current_participants' => $originalActualCount]);
                $newClass->update(['current_participants' => $newActualCount]);
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

    /**
     * Toggle policy active status
     */
    public function toggleStatus(CancellationPolicy $cancellationPolicy)
    {
        $cancellationPolicy->update([
            'is_active' => !$cancellationPolicy->is_active
        ]);

        return response()->json([
            'data' => $cancellationPolicy->fresh(),
            'message' => $cancellationPolicy->is_active 
                ? 'Η πολιτική ενεργοποιήθηκε' 
                : 'Η πολιτική απενεργοποιήθηκε'
        ]);
    }

    /**
     * Get statistics for dashboard
     */
    public function getStatistics()
    {
        $totalPolicies = CancellationPolicy::count();
        $activePolicies = CancellationPolicy::where('is_active', true)->count();
        $averageHoursBefore = CancellationPolicy::where('is_active', true)->avg('hours_before') ?? 0;
        $maxReschedules = CancellationPolicy::where('is_active', true)->max('max_reschedules_per_month') ?? 0;

        return response()->json([
            'total_policies' => $totalPolicies,
            'active_policies' => $activePolicies,
            'inactive_policies' => $totalPolicies - $activePolicies,
            'average_hours_before' => round($averageHoursBefore, 1),
            'max_reschedules_allowed' => $maxReschedules
        ]);
    }

    /**
     * Get available class types and packages for policy configuration
     */
    public function getConfigurationOptions()
    {
        $classTypes = [
            ['value' => 'group', 'label' => 'Ομαδικά Μαθήματα'],
            ['value' => 'personal', 'label' => 'Προσωπική Προπόνηση'],
            ['value' => 'ems', 'label' => 'EMS Training'],
            ['value' => 'pilates', 'label' => 'Pilates'],
            ['value' => 'yoga', 'label' => 'Yoga'],
            ['value' => 'hiit', 'label' => 'HIIT']
        ];

        $packages = DB::table('packages')
            ->select('id as value', 'name as label')
            ->where('is_active', true)
            ->get()
            ->toArray();

        return response()->json([
            'class_types' => $classTypes,
            'packages' => $packages
        ]);
    }

    /**
     * Seed test data (development only)
     */
    public function seedTestData()
    {
        if (!app()->environment(['local', 'testing'])) {
            return response()->json(['error' => 'Not available in production'], 403);
        }

        $policies = [
            [
                'name' => 'Βασική Πολιτική',
                'description' => 'Στάνταρ πολιτική ακύρωσης για όλα τα μαθήματα',
                'hours_before' => 24,
                'penalty_percentage' => 50,
                'allow_reschedule' => true,
                'reschedule_hours_before' => 12,
                'max_reschedules_per_month' => 3,
                'priority' => 1,
                'is_active' => true,
                'applicable_to' => ['class_types' => ['group'], 'package_ids' => []]
            ],
            [
                'name' => 'Προσωπική Προπόνηση',
                'description' => 'Αυστηρότερη πολιτική για προσωπικές προπονήσεις',
                'hours_before' => 48,
                'penalty_percentage' => 75,
                'allow_reschedule' => true,
                'reschedule_hours_before' => 24,
                'max_reschedules_per_month' => 2,
                'priority' => 2,
                'is_active' => true,
                'applicable_to' => ['class_types' => ['personal'], 'package_ids' => []]
            ],
            [
                'name' => 'Ευέλικτη Πολιτική',
                'description' => 'Ευέλικτη πολιτική για VIP μέλη',
                'hours_before' => 6,
                'penalty_percentage' => 25,
                'allow_reschedule' => true,
                'reschedule_hours_before' => 3,
                'max_reschedules_per_month' => 5,
                'priority' => 3,
                'is_active' => false,
                'applicable_to' => ['class_types' => [], 'package_ids' => []]
            ]
        ];

        foreach ($policies as $policyData) {
            CancellationPolicy::updateOrCreate(
                ['name' => $policyData['name']],
                $policyData
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Δημιουργήθηκαν 3 test πολιτικές ακύρωσης',
            'policies' => CancellationPolicy::orderBy('priority')->get()
        ]);
    }

    /**
     * Clear test data (development only)
     */
    public function clearTestData()
    {
        if (!app()->environment(['local', 'testing'])) {
            return response()->json(['error' => 'Not available in production'], 403);
        }

        $count = CancellationPolicy::count();
        CancellationPolicy::truncate();

        return response()->json([
            'success' => true,
            'message' => "Διαγράφηκαν {$count} πολιτικές ακύρωσης"
        ]);
    }
}