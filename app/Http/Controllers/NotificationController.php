<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of notifications.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Notification::with(['creator', 'recipients'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $notifications = $query->paginate($request->get('per_page', 15));

        // Add statistics to each notification
        $notifications->getCollection()->transform(function ($notification) {
            $notification->stats = $this->notificationService->getNotificationStats($notification);
            return $notification;
        });

        return response()->json($notifications);
    }

    /**
     * Store a newly created notification.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'in:info,warning,success,error',
            'priority' => 'in:low,medium,high',
            'channels' => 'array',
            'channels.*' => 'in:in_app,email,sms',
            'filters' => 'array',
            'scheduled_at' => 'nullable|date|after:now',
            'send_now' => 'boolean',
        ]);

        $validated['status'] = $request->get('send_now', false) ? 'sent' : 'draft';

        $notification = $this->notificationService->createNotification($validated);

        return response()->json([
            'message' => 'Notification created successfully',
            'notification' => $notification->load('creator'),
        ], 201);
    }

    /**
     * Display the specified notification.
     */
    public function show(Notification $notification): JsonResponse
    {
        $notification->load(['creator', 'recipients.user']);
        $notification->stats = $this->notificationService->getNotificationStats($notification);

        return response()->json($notification);
    }

    /**
     * Update the specified notification.
     */
    public function update(Request $request, Notification $notification): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'string|max:255',
            'message' => 'string',
            'type' => 'in:info,warning,success,error',
            'priority' => 'in:low,medium,high',
            'channels' => 'array',
            'channels.*' => 'in:in_app,email,sms',
            'filters' => 'array',
            'scheduled_at' => 'nullable|date|after:now',
            'status' => 'in:draft,scheduled',
        ]);

        $notification = $this->notificationService->updateNotification($notification, $validated);

        return response()->json([
            'message' => 'Notification updated successfully',
            'notification' => $notification->load('creator'),
        ]);
    }

    /**
     * Remove the specified notification.
     */
    public function destroy(Notification $notification): JsonResponse
    {
        if ($notification->status === 'sent') {
            return response()->json([
                'message' => 'Cannot delete a sent notification',
            ], 422);
        }

        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted successfully',
        ]);
    }

    /**
     * Send a notification immediately.
     */
    public function send(Notification $notification): JsonResponse
    {
        try {
            $this->notificationService->sendNotification($notification);

            return response()->json([
                'message' => 'Notification sent successfully',
                'notification' => $notification->fresh()->load('creator'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Preview recipients for given filters.
     */
    public function previewRecipients(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'filters' => 'required|array',
        ]);

        $preview = $this->notificationService->previewRecipients($validated['filters']);

        return response()->json($preview);
    }

    /**
     * Get user's notifications.
     */
    public function userNotifications(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $query = $user->notifications()
            ->with('notification')
            ->delivered()
            ->orderBy('delivered_at', 'desc');

        // Filter unread only
        if ($request->get('unread_only', false)) {
            $query->unread();
        }

        $notifications = $query->paginate($request->get('per_page', 15));

        return response()->json($notifications);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(NotificationRecipient $recipient): JsonResponse
    {
        // Ensure the recipient belongs to the authenticated user
        if ($recipient->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->notificationService->markAsRead($recipient);

        return response()->json([
            'message' => 'Notification marked as read',
            'recipient' => $recipient->fresh(),
        ]);
    }

    /**
     * Mark all notifications as read for the user.
     */
    public function markAllAsRead(): JsonResponse
    {
        $user = Auth::user();
        
        $user->notifications()
            ->unread()
            ->get()
            ->each(function ($recipient) {
                $this->notificationService->markAsRead($recipient);
            });

        return response()->json([
            'message' => 'All notifications marked as read',
        ]);
    }

    /**
     * Get notification statistics for dashboard.
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_sent' => Notification::sent()->count(),
            'total_scheduled' => Notification::where('status', 'scheduled')->count(),
            'total_draft' => Notification::where('status', 'draft')->count(),
            'sent_today' => Notification::sent()->whereDate('sent_at', today())->count(),
            'average_read_rate' => Notification::sent()
                ->where('delivered_count', '>', 0)
                ->selectRaw('AVG(read_count * 100.0 / delivered_count) as avg_rate')
                ->value('avg_rate') ?? 0,
        ];

        return response()->json($stats);
    }
}
