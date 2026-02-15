<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\GymClass;
use App\Http\Controllers\WaitlistController;
use App\Models\CancellationPolicy;
use App\Models\BookingReschedule;
use Carbon\Carbon;
use App\Events\BookingCreated;
use App\Events\BookingCancelled;
use App\Traits\ApiResponseTrait;
use App\Traits\SearchableTrait;
use App\Notifications\Bookings\BookingConfirmationNotification;
use App\Notifications\Bookings\BookingCancelledNotification;
use Illuminate\Support\Facades\Notification;
use App\Services\BookingCompletionService;
use Illuminate\Http\JsonResponse;

class BookingController extends Controller
{
    use ApiResponseTrait, SearchableTrait;

    protected BookingCompletionService $bookingCompletionService;

    public function __construct(BookingCompletionService $bookingCompletionService)
    {
        $this->bookingCompletionService = $bookingCompletionService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Booking::with('user', 'store', 'service', 'gymClass', 'fitnessClass');

        // Get authenticated user from Sanctum token
        $authUser = $request->user('sanctum');
        $isAdmin = $authUser && in_array($authUser->role, ['admin', 'trainer']);

        // For non-admin users, filter by their own user_id
        if (!$isAdmin) {
            if (!$authUser) {
                // No authenticated user - return empty array
                return response()->json([]);
            }

            // Regular user can only see their own bookings
            $query->where('user_id', $authUser->id);
            // Only show active bookings for regular users
            $query->where('status', '!=', 'cancelled');
            // Only show future bookings for regular users
            $now = now()->setTimezone(config('app.timezone'));
            $query->where(function($q) use ($now) {
                $q->where('date', '>', $now->toDateString())
                  ->orWhere(function($subQuery) use ($now) {
                      $subQuery->where('date', '=', $now->toDateString())
                               ->whereRaw("CONCAT(date, ' ', time) > ?", [$now->toDateTimeString()]);
                  });
            });
        }

        // Admin requests can filter by user_id if provided
        if ($isAdmin && $request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }


        if ($request->has('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('instructor')) {
            $this->addSafeLikeWhere($query, 'instructor', $request->instructor);
        }

        if ($request->has('store_id') && $request->store_id) {
            $query->where('store_id', $request->store_id);
        }

        $bookings = $query->orderBy('date')->orderBy('time')->get();

        return response()->json($bookings);
    }

    /**
     * Get user's past bookings for workout history
     */
    public function history(Request $request): JsonResponse
    {
        // Get authenticated user
        $authUser = $request->user();
        $isAdminOrTrainer = $authUser && in_array($authUser->role, ['admin', 'trainer']);

        // Determine which user's history to show
        $userId = null;
        if ($isAdminOrTrainer && $request->has('user_id')) {
            // Admin/trainer can view any user's history
            $userId = $request->get('user_id');
        } elseif ($authUser) {
            // Regular user sees their own history
            $userId = $authUser->id;
        }

        if (!$userId) {
            return response()->json([]);
        }

        $now = now();
        $bookings = Booking::with(['user', 'store', 'service', 'gymClass', 'fitnessClass', 'muscleGroups'])
            ->where('user_id', $userId)
            ->where(function($q) use ($now) {
                $q->where('date', '<', $now->toDateString())
                  ->orWhere(function($subQuery) use ($now) {
                      $subQuery->where('date', '=', $now->toDateString())
                               ->whereRaw("CONCAT(date, ' ', time) <= ?", [$now->toDateTimeString()]);
                  });
            })
            ->where('status', '!=', 'cancelled')
            ->orderBy('date', 'desc')
            ->orderBy('time', 'desc')
            ->get()
            ->map(function ($booking) {
                $bookingData = $booking->toArray();

                // Add muscle groups data
                $bookingData['muscle_groups'] = $booking->muscleGroups ? $booking->muscleGroups->muscle_groups : null;
                $bookingData['muscle_groups_recorded'] = $booking->muscleGroups !== null;

                // Add class details from either gymClass or fitnessClass
                if ($booking->fitnessClass) {
                    $bookingData['class_type'] = $booking->fitnessClass->type;
                    $bookingData['class_details'] = [
                        'id' => $booking->fitnessClass->id,
                        'name' => $booking->fitnessClass->name,
                        'type' => $booking->fitnessClass->type,
                        'instructor' => $booking->fitnessClass->instructor,
                        'location' => $booking->fitnessClass->location,
                        'description' => $booking->fitnessClass->description,
                    ];
                } elseif ($booking->gymClass) {
                    $bookingData['class_type'] = $booking->gymClass->class_type ?? null;
                    $bookingData['class_details'] = [
                        'id' => $booking->gymClass->id,
                        'class_type' => $booking->gymClass->class_type,
                        'trainer_name' => $booking->gymClass->trainer_name,
                    ];
                }

                return $bookingData;
            });

        return response()->json($bookings);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'user_id' => 'nullable|integer',
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'class_id' => 'nullable|integer',
            'class_name' => 'required|string|max:255',
            'instructor' => 'required|string|max:255',
            'service_id' => 'nullable|exists:services,id',
            'date' => 'required|date',
            'time' => 'required|string',
            'type' => 'required|string',
            'location' => 'nullable|string|max:255',
        ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Booking validation failed', [
                'request_data' => $request->all(),
                'errors' => $e->errors()
            ]);
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        }
        
        // Get authenticated user from Sanctum token
        $authUser = $request->user();

        if (!$authUser) {
            return $this->unauthorizedResponse('Πρέπει να είστε συνδεδεμένος για να κάνετε κράτηση.');
        }

        // Determine the customer user for this booking
        $user = null;
        $isAdminOrTrainer = in_array($authUser->role, ['admin', 'trainer']);

        if ($isAdminOrTrainer && !empty($validated['user_id'])) {
            // Admin/trainer creating booking for another user
            $user = \App\Models\User::find($validated['user_id']);
            if (!$user) {
                return $this->notFoundResponse('Ο χρήστης δεν βρέθηκε.');
            }
        } else {
            // Regular user creating booking for themselves
            $user = $authUser;
            $validated['user_id'] = $authUser->id;
        }

        // Check for duplicate booking - PREVENT DOUBLE BOOKINGS
        if ($user && !empty($validated['class_id'])) {
            $existingBooking = Booking::where('user_id', $user->id)
                ->where('class_id', $validated['class_id'])
                ->whereDate('date', $validated['date']) // Use whereDate for proper date comparison
                ->where('time', $validated['time']) // Add time check for extra safety
                ->whereIn('status', ['confirmed', 'waitlist'])
                ->first();
                
            if ($existingBooking) {
                \Log::info('Duplicate booking attempt blocked', [
                    'user_id' => $user->id,
                    'class_id' => $validated['class_id'],
                    'date' => $validated['date'],
                    'time' => $validated['time'],
                    'existing_booking_id' => $existingBooking->id
                ]);
                
                return $this->conflictResponse('Έχετε ήδη κράτηση για αυτό το μάθημα.');
            }
        }
        
        // Check if customer has available sessions for the selected service
        // Skip validation if admin/trainer is creating bookings for customers
        if ($user && !$isAdminOrTrainer) {
            // NEW APPROACH: Use class type to validate against user packages
            // This prevents issues where class names are too specific (e.g., "Pilates Personal")
            // but the user has a broader package (e.g., "Personal Training")

            $classType = $validated['type'] ?? null;

            // Get user's active packages with services
            $userPackages = \App\Models\UserPackage::where('user_id', $user->id)
                ->where('status', 'active')
                ->where('is_frozen', false)
                ->where('remaining_sessions', '>', 0)
                ->where(function($query) {
                    $query->whereNull('expiry_date')
                          ->orWhere('expiry_date', '>=', now()->toDateString());
                })
                ->with('package.services')
                ->get();

            if ($userPackages->isEmpty()) {
                return $this->businessValidationErrorResponse(
                    'Δεν έχετε ενεργό πακέτο με διαθέσιμες συνεδρίες.',
                    'NO_ACTIVE_PACKAGE'
                );
            }

            // If we have a class type, validate it against allowed class types for user's packages
            if ($classType) {
                $allowedClassTypes = $this->getAllowedClassTypesForPackages($userPackages);

                if (!in_array($classType, $allowedClassTypes)) {
                    $classTypeName = $this->getClassTypeName($classType);
                    return $this->businessValidationErrorResponse(
                        "Δεν έχετε ενεργό πακέτο για {$classTypeName} μαθήματα.",
                        'INVALID_CLASS_TYPE_FOR_PACKAGE'
                    );
                }

                \Log::info('Booking validation passed - class type allowed', [
                    'user_id' => $user->id,
                    'class_type' => $classType,
                    'allowed_types' => $allowedClassTypes
                ]);
            }
        }
        
        // Set default customer info if not provided
        if (empty($validated['customer_name'])) {
            $validated['customer_name'] = $user->name ?? 'Guest User';
        }
        if (empty($validated['customer_email'])) {
            $validated['customer_email'] = $user->email ?? 'guest@example.com';
        }
        
        $validated['status'] = 'confirmed';
        $validated['attended'] = false;
        $validated['booking_time'] = now();
        
        // If booking for a class, check if full and handle waitlist
        if (!empty($validated['class_id'])) {
            try {
                $gymClass = GymClass::with('instructor')->find($validated['class_id']);
                if ($gymClass) {
                    // Use the requested date and time from the frontend, not the class template
                    // Only copy class metadata like name, instructor, and location
                    $validated['class_name'] = $gymClass->name;
                    $validated['instructor'] = $gymClass->instructor ? $gymClass->instructor->name : 'TBD';
                    $validated['location'] = $gymClass->location;
                    
                    if ($gymClass->isFull()) {
                        // Add to waitlist instead of rejecting
                        $validated['status'] = 'waitlist';
                        $booking = Booking::create($validated);
                        
                        // ΔΙΟΡΘΩΣΗ: Προσθήκη στον class_waitlists πίνακα επίσης
                        if ($user) {
                            // Get next position in waitlist
                            $lastPosition = \App\Models\ClassWaitlist::where('class_id', $gymClass->id)
                                ->max('position') ?? 0;
                            
                            \App\Models\ClassWaitlist::create([
                                'class_id' => $gymClass->id,
                                'user_id' => $user->id,
                                'position' => $lastPosition + 1,
                                'status' => 'waiting'
                            ]);
                        }
                        
                        // Dispatch BookingCreated event for waitlist booking too
                        BookingCreated::dispatch($booking);
                        
                        // Send waitlist booking confirmation email to user
                        if ($user) {
                            $user->notify(new BookingConfirmationNotification($booking));
                        }
                        
                        // Notify admins about new waitlist booking
                        $admins = \App\Models\User::where('role', 'admin')->get();
                        foreach ($admins as $admin) {
                            $admin->notify(new BookingConfirmationNotification($booking));
                        }
                        
                        return $this->createdResponse([
                            'booking' => $booking->load('user', 'store'),
                            'waitlist' => true
                        ], 'Μπήκατε στη λίστα αναμονής. Θα ενημερωθείτε αυτόματα αν υπάρξει διαθέσιμη θέση.');
                    }
                    
                    // Store the gym class for later update (after booking creation)
                    $gymClassToUpdate = $gymClass;
                }
            } catch (\Exception $e) {
                // Continue without class validation if there's an error
            }
        }
        
        $booking = Booking::create($validated);
        
        // Dispatch BookingCreated event (handles participants count & session deduction)
        BookingCreated::dispatch($booking);
        
        // Send booking confirmation email to user
        if ($user) {
            $booking->load(['gymClass.instructor', 'user']); // Eager load for email
            $user->notify(new BookingConfirmationNotification($booking));
        }
        
        // Notify admins about new booking
        $admins = \App\Models\User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new BookingConfirmationNotification($booking));
        }
        
        return $this->createdResponse(
            ['booking' => $booking->load('user', 'store')],
            'Η κράτηση πραγματοποιήθηκε επιτυχώς.'
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Booking $booking): JsonResponse
    {
        return response()->json($booking->load('user', 'store', 'service'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Booking $booking): JsonResponse
    {
        $validated = $request->validate([
            'store_id' => 'sometimes|exists:stores,id',
            'customer_name' => 'sometimes|string|max:255',
            'customer_email' => 'sometimes|email|max:255',
            'class_name' => 'sometimes|string|max:255',
            'instructor' => 'sometimes|string|max:255',
            'service_id' => 'sometimes|exists:services,id',
            'date' => 'sometimes|date',
            'time' => 'sometimes|string',
            'start_time' => 'sometimes|string',
            'end_time' => 'sometimes|string',
            'status' => 'sometimes|in:confirmed,cancelled,completed,no_show',
            'type' => 'sometimes|string',
            'attended' => 'sometimes|boolean',
            'location' => 'nullable|string|max:255',
            'cancellation_reason' => 'nullable|string',
        ]);

        $booking->update($validated);
        return response()->json($booking->load('user', 'store', 'service'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Booking $booking): JsonResponse
    {
        DB::transaction(function () use ($booking) {
            // Decrement participants if class exists and booking was confirmed
            if ($booking->class_id && in_array($booking->status, ['confirmed', 'waitlist'])) {
                $gymClass = GymClass::find($booking->class_id);
                if ($gymClass && $gymClass->current_participants > 0) {
                    $gymClass->decrement('current_participants');
                }
            }

            // Fire cancellation event to handle session refund
            if ($booking->status === 'confirmed') {
                event(new BookingCancelled($booking, 'confirmed'));
            }

            $booking->delete();
        });

        return response()->json(['message' => 'Booking deleted successfully']);
    }

    /**
     * Complete a booking (mark as attended and deduct session from package)
     */
    public function complete(Request $request, $bookingId): JsonResponse
    {
        try {
            // Validate request
            $validated = $request->validate([
                'trainer_notes' => 'nullable|string|max:2000',
            ]);

            // Get authenticated user
            $authUser = $request->user();

            // Fallback: Try to get auth user from X-User-ID header
            if (!$authUser && $request->hasHeader('X-User-ID')) {
                $authUserId = $request->header('X-User-ID');
                $authUser = \App\Models\User::find($authUserId);
            }

            if (!$authUser) {
                return $this->unauthorizedResponse('Πρέπει να είστε συνδεδεμένος για αυτή την ενέργεια.');
            }

            // Complete the booking using the service
            $result = $this->bookingCompletionService->completeBooking(
                (int) $bookingId,
                $authUser->id,
                $validated['trainer_notes'] ?? null
            );

            \Log::info('Booking completed', [
                'booking_id' => $bookingId,
                'completed_by' => $authUser->id,
                'completed_by_name' => $authUser->name,
                'is_guest' => $result['is_guest'],
                'has_trainer_notes' => !empty($validated['trainer_notes']),
                'remaining_sessions' => $result['user_package'] ? $result['user_package']->remaining_sessions : 'N/A (guest)',
            ]);

            return response()->json([
                'success' => true,
                'message' => $result['is_guest']
                    ? 'Η δοκιμαστική προπόνηση ολοκληρώθηκε επιτυχώς.'
                    : 'Η προπόνηση ολοκληρώθηκε επιτυχώς.',
                'data' => [
                    'booking' => $result['booking'],
                    'is_guest' => $result['is_guest'],
                    'remaining_sessions' => $result['user_package'] ? $result['user_package']->remaining_sessions : null,
                    'package_name' => $result['user_package'] ? ($result['user_package']->package->name ?? 'Πακέτο') : null,
                ]
            ]);
        } catch (\App\Exceptions\BusinessValidationException $e) {
            return $this->businessValidationErrorResponse($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            \Log::error('Failed to complete booking', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Αποτυχία ολοκλήρωσης προπόνησης: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel a booking
     */
    public function cancel(Request $request, Booking $booking): JsonResponse
    {
        // Check if user owns this booking
        $userId = null;
        if ($request->has('user_id')) {
            $userId = $request->get('user_id');
        } elseif ($request->hasHeader('X-User-ID')) {
            $userId = $request->header('X-User-ID');
        }
        
        if ($userId && $booking->user_id != $userId) {
            return $this->forbiddenResponse('Unauthorized to cancel this booking');
        }
        
        if ($booking->status === 'cancelled') {
            return $this->errorResponse('Booking already cancelled', 400);
        }
        
        $validated = $request->validate([
            'cancellation_reason' => 'nullable|string',
        ]);
        
        // Skip cancellation policy check for now
        $penaltyAmount = 0;
        
        DB::beginTransaction();
        try {
            // Update booking status
            $previousStatus = $booking->status;
            $booking->update([
                'status' => 'cancelled',
                'cancellation_reason' => $validated['cancellation_reason'] ?? null,
            ]);
            
            // Dispatch BookingCancelled event (handles session refund & participants count)
            BookingCancelled::dispatch($booking, $previousStatus);
            
            // Send booking cancellation email to user
            if ($booking->user) {
                $booking->load('gymClass.instructor'); // Eager load for email
                $booking->user->notify(new BookingCancelledNotification(
                    $booking,
                    $validated['cancellation_reason'] ?? null,
                    'user'
                ));
            }
            
            // Notify admins about booking cancellation
            $admins = \App\Models\User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new BookingCancelledNotification(
                    $booking,
                    $validated['cancellation_reason'] ?? null,
                    'user'
                ));
            }
            
            // Log the cancellation activity
            // ActivityLogger::logBookingCancellation($booking);
            
            // Process waitlist if there's now a spot available
            if ($booking->class_id) {
                $gymClass = GymClass::find($booking->class_id);
                if ($gymClass && $gymClass->hasAvailableSpots()) {
                    $waitlistController = new WaitlistController();
                    $waitlistController->processNextInLine($gymClass);
                }
            }
            
            DB::commit();
            
            return $this->successResponse([
                'booking' => $booking->load('user', 'store'),
                'penalty_percentage' => $penaltyAmount
            ], $penaltyAmount > 0
                ? "Η κράτηση ακυρώθηκε με χρέωση {$penaltyAmount}%"
                : 'Η κράτηση ακυρώθηκε επιτυχώς');
            
        } catch (\Exception $e) {
            DB::rollback();
            return $this->serverErrorResponse('Σφάλμα κατά την ακύρωση');
        }
    }

    /**
     * Get allowed class types for user's packages
     * This maps the services in user's packages to class type values
     */
    private function getAllowedClassTypesForPackages($userPackages): array
    {
        $allowedClassTypes = [];

        foreach ($userPackages as $userPackage) {
            if ($userPackage->package && $userPackage->package->services) {
                foreach ($userPackage->package->services as $service) {
                    $serviceName = strtolower($service->name);

                    // Map service names to class type values
                    // This mapping must match the logic in ClassTypesController
                    if (strpos($serviceName, 'semi personal') !== false) {
                        $allowedClassTypes[] = 'semi-personal';
                        $allowedClassTypes[] = 'group';
                    } elseif (strpos($serviceName, 'personal training') !== false && strpos($serviceName, 'pilates') === false) {
                        $allowedClassTypes[] = 'personal';
                    } elseif (strpos($serviceName, 'pilates personal') !== false) {
                        $allowedClassTypes[] = 'personal-pilates';
                    } elseif (strpos($serviceName, 'pilates group') !== false) {
                        $allowedClassTypes[] = 'pilates';
                        $allowedClassTypes[] = 'group';
                    } elseif (strpos($serviceName, 'ems') !== false) {
                        $allowedClassTypes[] = 'ems';
                    } elseif (strpos($serviceName, 'cardio personal') !== false) {
                        $allowedClassTypes[] = 'cardio-personal';
                    }
                }
            }
        }

        return array_unique($allowedClassTypes);
    }

    /**
     * Mark booking as no-show/absent
     */
    public function markAbsent(Request $request, Booking $booking): JsonResponse
    {
        // Validate request
        $validated = $request->validate([
            'with_charge' => 'required|boolean',
            'reason' => 'nullable|string|max:500',
        ]);

        $withCharge = $validated['with_charge'];
        $reason = $validated['reason'] ?? 'Απουσία χρήστη';

        // Check if booking can be marked as absent
        if ($booking->status === 'no-show') {
            return $this->errorResponse('Η κράτηση έχει ήδη σημειωθεί ως απουσία', 400);
        }

        if ($booking->status === 'cancelled') {
            return $this->errorResponse('Η κράτηση έχει ακυρωθεί', 400);
        }

        if ($booking->status === 'completed') {
            return $this->errorResponse('Η κράτηση έχει ολοκληρωθεί', 400);
        }

        DB::beginTransaction();
        try {
            $previousStatus = $booking->status;

            // Update booking status
            $booking->update([
                'status' => 'no-show',
                'absence_reason' => $reason,
                'absence_with_charge' => $withCharge,
                'absence_marked_at' => now(),
                'absence_marked_by' => $request->user() ? $request->user()->id : null,
            ]);

            // If "without charge", refund the session
            if (!$withCharge && $booking->user_id) {
                // Find active user package and refund session
                $userPackage = \App\Models\UserPackage::where('user_id', $booking->user_id)
                    ->where('status', 'active')
                    ->whereDate('start_date', '<=', now())
                    ->where(function($query) {
                        $query->whereNull('expiry_date')
                            ->orWhereDate('expiry_date', '>=', now());
                    })
                    ->first();

                if ($userPackage && $userPackage->total_sessions !== null) {
                    // Refund one session
                    $userPackage->increment('remaining_sessions', 1);

                    \Log::info('Session refunded due to no-show without charge', [
                        'booking_id' => $booking->id,
                        'user_id' => $booking->user_id,
                        'package_id' => $userPackage->id,
                        'new_remaining' => $userPackage->remaining_sessions,
                    ]);
                }
            }

            // Log activity
            ActivityLogger::log(
                'booking_marked_absent',
                "Booking marked as absent: {$booking->user->name}",
                $booking,
                [
                    'booking_id' => $booking->id,
                    'user_id' => $booking->user_id,
                    'with_charge' => $withCharge,
                    'reason' => $reason,
                    'marked_by' => $request->user() ? $request->user()->name : 'System',
                ]
            );

            // If "without charge", notify admins
            if (!$withCharge) {
                $admins = \App\Models\User::where('role', 'admin')->get();
                $className = $booking->gymClass->name ?? $booking->fitnessClass->name ?? 'μάθημα';
                $message = "Απουσία χωρίς χρέωση: {$booking->user->name} για {$className} στις " . $booking->date->format('d/m/Y H:i');

                foreach ($admins as $admin) {
                    // Create notification in database
                    $admin->notifications()->create([
                        'type' => 'App\\Notifications\\AbsenceWithoutChargeNotification',
                        'data' => [
                            'booking_id' => $booking->id,
                            'user_name' => $booking->user->name,
                            'user_id' => $booking->user_id,
                            'class_name' => $className,
                            'date' => $booking->date->format('d/m/Y H:i'),
                            'reason' => $reason,
                            'message' => $message,
                        ],
                    ]);
                }

                \Log::info('Admin notified about absence without charge', [
                    'booking_id' => $booking->id,
                    'admin_count' => $admins->count(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $withCharge
                    ? 'Η κράτηση σημειώθηκε ως απουσία με χρέωση'
                    : 'Η κράτηση σημειώθηκε ως απουσία χωρίς χρέωση. Η συνεδρία επιστράφηκε.',
                'data' => [
                    'booking' => $booking->fresh(),
                    'with_charge' => $withCharge,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to mark booking as absent', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Αποτυχία σημείωσης απουσίας: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get friendly name for class type
     */
    private function getClassTypeName($classType): string
    {
        $names = [
            'personal' => 'Personal Training',
            'personal-pilates' => 'Pilates Personal',
            'pilates' => 'Pilates Group',
            'group' => 'Group',
            'ems' => 'EMS',
            'cardio-personal' => 'Cardio Personal',
            'semi-personal' => 'Semi Personal',
        ];

        return $names[$classType] ?? $classType;
    }
}
