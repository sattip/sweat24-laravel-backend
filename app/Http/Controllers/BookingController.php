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

class BookingController extends Controller
{
    use ApiResponseTrait;
    
    // Remove middleware for testing
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Booking::with('user');
        
        // Try multiple authentication methods
        $userId = null;
        $isAdmin = false;
        
        // Check request origin - if from admin panel port, treat as admin
        $origin = $request->header('Origin');
        $referer = $request->header('Referer');
        \Log::info('BookingController::index - Origin: ' . ($origin ?: 'none') . ', Referer: ' . ($referer ?: 'none'));
        
        if (($origin && str_contains($origin, ':5174')) || ($referer && str_contains($referer, ':5174'))) {
            $isAdmin = true;
            \Log::info('BookingController::index - Admin panel origin detected');
        }
        
        // Check if this is an admin request (Bearer token from admin panel)
        $authHeader = $request->header('Authorization');
        \Log::info('BookingController::index - Auth header: ' . ($authHeader ?: 'none'));
        
        if (!$isAdmin && $authHeader && str_starts_with($authHeader, 'Bearer ')) {
            // Verify it's actually a valid admin token
            try {
                $user = $request->user('sanctum');
                if ($user && $user->role === 'admin') {
                    $isAdmin = true;
                    \Log::info('BookingController::index - Valid admin request detected');
                } else {
                    // For now, assume any Bearer token is admin
                    $isAdmin = true;
                    \Log::info('BookingController::index - Bearer token treated as admin');
                }
            } catch (\Exception $e) {
                // If we can't verify, still treat as admin for now
                $isAdmin = true;
                \Log::info('BookingController::index - Bearer token (unverified) treated as admin');
            }
        }
        
        // Only check other auth methods if NOT admin
        if (!$isAdmin) {
            // Check if user_id is passed as parameter (for API calls)
            if ($request->has('user_id')) {
                $userId = $request->get('user_id');
                \Log::info('BookingController::index - user_id parameter found: ' . $userId);
            }
            // Check session-based authentication via custom header
            elseif ($request->hasHeader('X-User-ID')) {
                $userId = $request->header('X-User-ID');
            }
            // If no auth found and not admin, return empty array
            else {
                \Log::info('BookingController::index - No auth found, returning empty array');
                return response()->json([]);
            }
        }
        
        // Filter by user if we have userId and not admin
        if ($userId && !$isAdmin) {
            $query->where('user_id', $userId);
            // Only show active bookings for regular users
            $query->where('status', '!=', 'cancelled');
            // Only show future bookings (from current date and time) for regular users
            $now = now()->setTimezone(config('app.timezone'));
            $query->where(function($q) use ($now) {
                $q->where('date', '>', $now->toDateString())
                  ->orWhere(function($subQuery) use ($now) {
                      $subQuery->where('date', '=', $now->toDateString())
                               ->whereRaw("CONCAT(date, ' ', time) > ?", [$now->toDateTimeString()]);
                  });
            });
            \Log::info('BookingController::index - Filtering by user_id: ' . $userId);
        }
        
        // Admin requests get all bookings without time filtering
        
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
        
        \Log::info('BookingController::index - Returning ' . $bookings->count() . ' bookings');
        
        // Return just the bookings array to maintain compatibility
        return response()->json($bookings);
    }

    /**
     * Get user's past bookings for workout history
     */
    public function history(Request $request)
    {
        $query = Booking::with('user');
        
        // Get user ID from parameter
        $userId = $request->get('user_id');
        if (!$userId) {
            return response()->json([]);
        }
        
        // Filter by user and show only past bookings
        $now = now();
        $query->where('user_id', $userId)
              ->where(function($q) use ($now) {
                  $q->where('date', '<', $now->toDateString())
                    ->orWhere(function($subQuery) use ($now) {
                        $subQuery->where('date', '=', $now->toDateString())
                                 ->whereRaw("CONCAT(date, ' ', time) <= ?", [$now->toDateTimeString()]);
                    });
              })
              ->where('status', '!=', 'cancelled');
        
        $bookings = $query->orderBy('date', 'desc')->orderBy('time', 'desc')->get();
        
        \Log::info('BookingController::history - Returning ' . $bookings->count() . ' past bookings for user ' . $userId);
        
        return response()->json($bookings);
    }

    /**
     * History endpoint (bypass auth for testing)
     */
    public function testHistory(Request $request)
    {
        // Direct history call without auth
        $userId = $request->get('user_id');
        if (!$userId) {
            return response()->json([]);
        }
        
        $query = Booking::query();
        $now = now();
        $bookings = $query->where('user_id', $userId)
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
              ->get();
        
        return response()->json($bookings);
    }

    /**
     * Test endpoint without any middleware
     */
    public function test(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Booking API is working!',
            'data' => $request->all()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'nullable|integer',
                'customer_name' => 'nullable|string|max:255',
                'customer_email' => 'nullable|email|max:255',
                'class_id' => 'nullable|integer',
                'class_name' => 'required|string|max:255',
                'instructor' => 'required|string|max:255',
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
        
        // Try to detect the authenticated user using multiple methods
        $user = null;
        $userId = null;
        
        // Check if user_id is passed as parameter (for API calls)
        if ($request->has('user_id')) {
            $userId = $request->get('user_id');
        }
        // Check session-based authentication via custom header
        elseif ($request->hasHeader('X-User-ID')) {
            $userId = $request->header('X-User-ID');
        }
        
        // Set the user_id to the detected user if not provided
        if (empty($validated['user_id'])) {
            $validated['user_id'] = $userId ?? 1; // Default to Admin User for now
        }
        
        // Find the user if we have a user_id
        if ($validated['user_id']) {
            $user = \App\Models\User::find($validated['user_id']);
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
        
        // Check if user has available sessions before booking
        if ($user) {
            $hasAvailableSessions = \App\Models\UserPackage::where('user_id', $user->id)
                ->where('status', 'active')
                ->where('remaining_sessions', '>', 0)
                ->exists();
            
            if (!$hasAvailableSessions) {
                return $this->forbiddenResponse('Δεν έχετε διαθέσιμες συνεδρίες στο πακέτο σας.');
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
                    // Override date and time with the actual class values
                    $validated['date'] = $gymClass->date;
                    $validated['time'] = $gymClass->time;
                    $validated['class_name'] = $gymClass->name;
                    $validated['instructor'] = $gymClass->instructor ? $gymClass->instructor->name : 'TBD';
                    $validated['location'] = $gymClass->location;
                    
                    if ($gymClass->isFull()) {
                        // Add to waitlist instead of rejecting
                        $validated['status'] = 'waitlist';
                        $booking = Booking::create($validated);
                        
                        // Dispatch BookingCreated event for waitlist booking too
                        BookingCreated::dispatch($booking);
                        
                        return $this->createdResponse([
                            'booking' => $booking->load('user'),
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
        
        return $this->createdResponse(
            ['booking' => $booking->load('user')],
            'Η κράτηση πραγματοποιήθηκε επιτυχώς.'
        );
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
            $booking->update([
                'status' => 'cancelled',
                'cancellation_reason' => $validated['cancellation_reason'] ?? null,
            ]);
            
            // Dispatch BookingCancelled event (handles session refund & participants count)
            BookingCancelled::dispatch($booking);
            
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
                'booking' => $booking->load('user'),
                'penalty_percentage' => $penaltyAmount
            ], $penaltyAmount > 0 
                ? "Η κράτηση ακυρώθηκε με χρέωση {$penaltyAmount}%" 
                : 'Η κράτηση ακυρώθηκε επιτυχώς');
            
        } catch (\Exception $e) {
            DB::rollback();
            return $this->serverErrorResponse('Σφάλμα κατά την ακύρωση');
        }
    }
}
