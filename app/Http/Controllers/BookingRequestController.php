<?php

namespace App\Http\Controllers;

use App\Models\BookingRequest;
use App\Models\Instructor;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BookingRequestController extends Controller
{
    /**
     * Display a listing of all booking requests for admin
     */
    public function index(Request $request)
    {
        $query = BookingRequest::with(['user', 'instructor', 'processedBy']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('service_type')) {
            $query->where('service_type', $request->service_type);
        }

        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $bookingRequests = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($bookingRequests);
    }

    /**
     * Store a newly created booking request from client app
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_type' => 'required|in:ems,personal',
            'instructor_id' => 'nullable|exists:instructors,id',
            'client_name' => 'required|string|max:255',
            'client_email' => 'required|email|max:255',
            'client_phone' => 'required|string|max:20',
            'preferred_dates' => 'required|array|min:1|max:3',
            'preferred_dates.*' => 'required|date|after:today',
            'preferred_times' => 'required|array|min:1|max:3',
            'preferred_times.*' => 'required|string|in:morning,afternoon,evening',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Set user_id if authenticated
        if (Auth::check()) {
            $validated['user_id'] = Auth::id();
        }

        $bookingRequest = DB::transaction(function () use ($validated) {
            $bookingRequest = BookingRequest::create($validated);

            // Log activity only if user is authenticated
            if (Auth::check()) {
                ActivityLog::create([
                    'user_id' => Auth::id(),
                    'activity_type' => 'booking_request',
                    'action' => 'created',
                    'model_type' => BookingRequest::class,
                    'model_id' => $bookingRequest->id,
                    'properties' => [
                        'service_type' => $bookingRequest->service_type,
                        'client_name' => $bookingRequest->client_name,
                    ],
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            }

            return $bookingRequest;
        });

        return response()->json([
            'message' => 'Booking request submitted successfully',
            'data' => $bookingRequest->load(['instructor']),
        ], 201);
    }

    /**
     * Display the specified booking request
     */
    public function show(BookingRequest $bookingRequest)
    {
        // Check authorization
        if (!Auth::user()->isAdmin() && $bookingRequest->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($bookingRequest->load(['user', 'instructor', 'processedBy']));
    }

    /**
     * Get booking requests for the authenticated user
     */
    public function userRequests(Request $request)
    {
        $query = BookingRequest::where('user_id', Auth::id())
            ->orWhere('client_email', Auth::user()->email)
            ->with(['instructor', 'processedBy']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $bookingRequests = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json($bookingRequests);
    }

    /**
     * Confirm a booking request (Admin only)
     */
    public function confirm(Request $request, BookingRequest $bookingRequest)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'confirmed_date' => 'required|date|after_or_equal:today',
            'confirmed_time' => 'required|date_format:H:i',
            'instructor_id' => 'nullable|exists:instructors,id',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($bookingRequest, $validated) {
            $bookingRequest->confirm(
                $validated['confirmed_date'],
                $validated['confirmed_time'],
                $validated['instructor_id'] ?? $bookingRequest->instructor_id,
                $validated['admin_notes'] ?? null
            );

            // Log activity
            ActivityLog::create([
                'user_id' => Auth::id(),
                'activity_type' => 'booking_request',
                'action' => 'confirmed',
                'model_type' => BookingRequest::class,
                'model_id' => $bookingRequest->id,
                'properties' => [
                    'confirmed_date' => $validated['confirmed_date'],
                    'confirmed_time' => $validated['confirmed_time'],
                ],
            ]);

            // TODO: Send notification email to client
        });

        return response()->json([
            'message' => 'Booking request confirmed successfully',
            'data' => $bookingRequest->fresh()->load(['user', 'instructor', 'processedBy']),
        ]);
    }

    /**
     * Reject a booking request (Admin only)
     */
    public function reject(Request $request, BookingRequest $bookingRequest)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($bookingRequest, $validated) {
            $bookingRequest->reject(
                $validated['rejection_reason'],
                $validated['admin_notes'] ?? null
            );

            // Log activity
            ActivityLog::create([
                'user_id' => Auth::id(),
                'activity_type' => 'booking_request',
                'action' => 'rejected',
                'model_type' => BookingRequest::class,
                'model_id' => $bookingRequest->id,
                'properties' => [
                    'rejection_reason' => $validated['rejection_reason'],
                ],
            ]);

            // TODO: Send notification email to client
        });

        return response()->json([
            'message' => 'Booking request rejected',
            'data' => $bookingRequest->fresh()->load(['user', 'instructor', 'processedBy']),
        ]);
    }

    /**
     * Cancel a booking request (by user or admin)
     */
    public function cancel(Request $request, BookingRequest $bookingRequest)
    {
        // Check authorization
        if (!Auth::user()->isAdmin() && $bookingRequest->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Only pending requests can be cancelled
        if (!$bookingRequest->isPending()) {
            return response()->json(['message' => 'Only pending requests can be cancelled'], 400);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($bookingRequest, $validated) {
            $bookingRequest->cancel($validated['reason'] ?? null);

            // Log activity
            ActivityLog::create([
                'user_id' => Auth::id(),
                'activity_type' => 'booking_request',
                'action' => 'cancelled',
                'model_type' => BookingRequest::class,
                'model_id' => $bookingRequest->id,
                'properties' => [
                    'cancelled_by' => Auth::user()->isAdmin() ? 'admin' : 'user',
                    'reason' => $validated['reason'] ?? null,
                ],
            ]);
        });

        return response()->json([
            'message' => 'Booking request cancelled successfully',
            'data' => $bookingRequest->fresh()->load(['user', 'instructor', 'processedBy']),
        ]);
    }

    /**
     * Get available instructors for a service type
     */
    public function getAvailableInstructors(Request $request)
    {
        $validated = $request->validate([
            'service_type' => 'required|in:ems,personal',
        ]);

        $serviceSpecialty = $validated['service_type'] === 'ems' ? 'EMS' : 'Personal Training';

        $instructors = Instructor::where('status', 'active')
            ->whereJsonContains('specialties', $serviceSpecialty)
            ->select('id', 'name', 'specialties', 'image_url')
            ->get();

        return response()->json($instructors);
    }

    /**
     * Get statistics for admin dashboard
     */
    public function statistics(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $stats = [
            'total' => BookingRequest::count(),
            'pending' => BookingRequest::pending()->count(),
            'confirmed' => BookingRequest::confirmed()->count(),
            'rejected' => BookingRequest::where('status', BookingRequest::STATUS_REJECTED)->count(),
            'completed' => BookingRequest::where('status', BookingRequest::STATUS_COMPLETED)->count(),
            'by_service' => [
                'ems' => BookingRequest::where('service_type', BookingRequest::SERVICE_EMS)->count(),
                'personal' => BookingRequest::where('service_type', BookingRequest::SERVICE_PERSONAL)->count(),
            ],
            'recent_requests' => BookingRequest::with(['user', 'instructor'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Mark a confirmed booking as completed (Admin only)
     */
    public function markCompleted(BookingRequest $bookingRequest)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$bookingRequest->isConfirmed()) {
            return response()->json(['message' => 'Only confirmed bookings can be marked as completed'], 400);
        }

        DB::transaction(function () use ($bookingRequest) {
            $bookingRequest->markAsCompleted();

            // Log activity
            ActivityLog::create([
                'user_id' => Auth::id(),
                'activity_type' => 'booking_request',
                'action' => 'completed',
                'model_type' => BookingRequest::class,
                'model_id' => $bookingRequest->id,
            ]);
        });

        return response()->json([
            'message' => 'Booking marked as completed',
            'data' => $bookingRequest->fresh()->load(['user', 'instructor', 'processedBy']),
        ]);
    }
} 