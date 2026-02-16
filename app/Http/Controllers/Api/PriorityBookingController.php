<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\GymClass;
use App\Models\PriorityBookingSettings;
use App\Services\PriorityBookingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class PriorityBookingController extends Controller
{
    protected $priorityService;

    public function __construct(PriorityBookingService $priorityService)
    {
        $this->priorityService = $priorityService;
    }

    /**
     * Get priority booking settings
     */
    public function getSettings(): JsonResponse
    {
        $settings = PriorityBookingSettings::getSettings();

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    /**
     * Update priority booking settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'default_priority_seats' => 'sometimes|integer|min:0',
            'priority_advance_hours' => 'sometimes|integer|min:1|max:168',
            'priority_release_hours' => 'sometimes|integer|min:1|max:72',
            'auto_release_enabled' => 'sometimes|boolean',
            'priority_system_enabled' => 'sometimes|boolean',
            'priority_packages' => 'sometimes|array',
            'priority_packages.*' => 'exists:packages,id',
        ]);

        $settings = PriorityBookingSettings::getSettings();
        $settings->update($validated);

        // Update users based on package changes if needed
        if (isset($validated['priority_packages'])) {
            $this->priorityService->updatePriorityUsersFromPackages();
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
            'data' => $settings
        ]);
    }

    /**
     * Grant priority access to a user
     */
    public function grantPriority(Request $request, $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $validated = $request->validate([
            'expires_at' => 'nullable|date|after:now',
            'hours_advance' => 'nullable|integer|min:1|max:168',
        ]);

        $expiresAt = isset($validated['expires_at']) ? Carbon::parse($validated['expires_at']) : null;
        $hoursAdvance = $validated['hours_advance'] ?? null;

        $this->priorityService->grantPriorityAccess($user, $expiresAt, $hoursAdvance);

        return response()->json([
            'success' => true,
            'message' => 'Priority access granted successfully',
            'data' => [
                'user_id' => $user->id,
                'has_priority_booking' => true,
                'priority_booking_expires_at' => $user->priority_booking_expires_at,
                'priority_booking_hours_advance' => $user->priority_booking_hours_advance,
            ]
        ]);
    }

    /**
     * Revoke priority access from a user
     */
    public function revokePriority($userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        
        $this->priorityService->revokePriorityAccess($user);

        return response()->json([
            'success' => true,
            'message' => 'Priority access revoked successfully',
            'data' => [
                'user_id' => $user->id,
                'has_priority_booking' => false,
            ]
        ]);
    }

    /**
     * Get users with priority access
     */
    public function getPriorityUsers(Request $request): JsonResponse
    {
        $query = User::where('has_priority_booking', true);

        if ($request->has('active_only')) {
            $query->where(function ($q) {
                $q->whereNull('priority_booking_expires_at')
                  ->orWhere('priority_booking_expires_at', '>', now());
            });
        }

        $users = $query->select('id', 'name', 'email', 'has_priority_booking', 
                                'priority_booking_expires_at', 'priority_booking_hours_advance')
                       ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Update class priority settings
     */
    public function updateClassPriority(Request $request, $classId): JsonResponse
    {
        $class = GymClass::findOrFail($classId);

        $validated = $request->validate([
            'priority_seats' => 'required|integer|min:0|max:' . $class->max_participants,
            'priority_booking_enabled' => 'sometimes|boolean',
        ]);

        // Ensure we don't reduce priority seats below already booked
        if ($validated['priority_seats'] < $class->priority_seats_booked) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot reduce priority seats below currently booked count',
                'current_booked' => $class->priority_seats_booked,
            ], 422);
        }

        $class->update($validated);

        // Calculate release time if not set
        if (!$class->priority_seats_release_at && $class->priority_booking_enabled) {
            $classDateTime = Carbon::parse($class->date . ' ' . $class->time);
            $settings = PriorityBookingSettings::getSettings();
            $class->update([
                'priority_seats_release_at' => $classDateTime->subHours($settings->priority_release_hours)
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Class priority settings updated',
            'data' => $class->getAvailabilityInfo()
        ]);
    }

    /**
     * Get class availability for a user
     */
    public function getClassAvailability($classId): JsonResponse
    {
        $class = GymClass::findOrFail($classId);
        $user = auth()->user();

        $availability = $this->priorityService->getClassAvailabilityForUser($class, $user);

        return response()->json([
            'success' => true,
            'data' => $availability
        ]);
    }

    /**
     * Manually release priority seats for a class
     */
    public function releasePrioritySeats($classId): JsonResponse
    {
        $class = GymClass::findOrFail($classId);
        
        $unusedBefore = $class->availablePrioritySeats();
        $class->releasePrioritySeats();
        
        return response()->json([
            'success' => true,
            'message' => 'Priority seats released',
            'data' => [
                'released_seats' => $unusedBefore,
                'availability' => $class->getAvailabilityInfo()
            ]
        ]);
    }

    /**
     * Sync priority users based on packages
     */
    public function syncPriorityFromPackages(): JsonResponse
    {
        $this->priorityService->updatePriorityUsersFromPackages();

        return response()->json([
            'success' => true,
            'message' => 'Priority users synced from packages'
        ]);
    }

    /**
     * Get priority booking stats
     */
    public function getStats(): JsonResponse
    {
        $stats = [
            'total_priority_users' => User::where('has_priority_booking', true)->count(),
            'active_priority_users' => User::where('has_priority_booking', true)
                ->where(function ($q) {
                    $q->whereNull('priority_booking_expires_at')
                      ->orWhere('priority_booking_expires_at', '>', now());
                })->count(),
            'upcoming_classes_with_priority' => GymClass::where('priority_booking_enabled', true)
                ->where('date', '>=', now()->toDateString())
                ->count(),
            'total_priority_seats_available' => GymClass::where('priority_booking_enabled', true)
                ->where('date', '>=', now()->toDateString())
                ->sum(\DB::raw('priority_seats - priority_seats_booked')),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}