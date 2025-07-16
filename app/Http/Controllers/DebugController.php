<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DebugController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
        
        // Only allow in development mode
        if (config('app.env') !== 'local') {
            abort(404, 'Debug endpoints only available in development mode');
        }
    }

    /**
     * Simulate receiving a notification for the current user
     */
    public function simulateReceiveNotification(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:500',
            'type' => 'nullable|in:info,warning,success,error',
            'priority' => 'nullable|in:low,medium,high',
        ]);

        $user = Auth::user();
        
        $title = $validated['title'] ?? '[DEBUG] Simulated Notification';
        $message = $validated['message'] ?? 'This is a simulated notification for debugging purposes. Sent at ' . now()->format('Y-m-d H:i:s');
        $type = $validated['type'] ?? 'info';
        $priority = $validated['priority'] ?? 'medium';

        try {
            // Create a notification directly for the current user
            $notification = $this->notificationService->createNotification([
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'priority' => $priority,
                'channels' => ['in_app'],
                'filters' => [
                    'inline' => [
                        'user_ids' => [$user->id]
                    ]
                ],
                'status' => 'sent'
            ]);

            Log::info('Debug notification simulated', [
                'user_id' => $user->id,
                'notification_id' => $notification->id,
                'title' => $title
            ]);

            return response()->json([
                'message' => 'Notification simulated successfully',
                'notification' => $notification,
                'user' => $user->name,
                'debug' => true
            ]);

        } catch (\Exception $e) {
            Log::error('Error simulating notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to simulate notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear all notifications for the current user
     */
    public function clearAllNotifications(Request $request): JsonResponse
    {
        $user = Auth::user();

        try {
            $deletedCount = $user->notifications()->delete();

            Log::info('Debug: All notifications cleared for user', [
                'user_id' => $user->id,
                'deleted_count' => $deletedCount
            ]);

            return response()->json([
                'message' => 'All notifications cleared successfully',
                'deleted_count' => $deletedCount,
                'user' => $user->name,
                'debug' => true
            ]);

        } catch (\Exception $e) {
            Log::error('Error clearing all notifications', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to clear notifications: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark all notifications as read for the current user
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = Auth::user();

        try {
            $updatedCount = $user->notifications()
                ->unread()
                ->update(['read_at' => now()]);

            Log::info('Debug: All notifications marked as read for user', [
                'user_id' => $user->id,
                'updated_count' => $updatedCount
            ]);

            return response()->json([
                'message' => 'All notifications marked as read',
                'updated_count' => $updatedCount,
                'user' => $user->name,
                'debug' => true
            ]);

        } catch (\Exception $e) {
            Log::error('Error marking all notifications as read', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to mark notifications as read: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark all notifications as unread for the current user
     */
    public function markAllAsUnread(Request $request): JsonResponse
    {
        $user = Auth::user();

        try {
            $updatedCount = $user->notifications()
                ->read()
                ->update(['read_at' => null]);

            Log::info('Debug: All notifications marked as unread for user', [
                'user_id' => $user->id,
                'updated_count' => $updatedCount
            ]);

            return response()->json([
                'message' => 'All notifications marked as unread',
                'updated_count' => $updatedCount,
                'user' => $user->name,
                'debug' => true
            ]);

        } catch (\Exception $e) {
            Log::error('Error marking all notifications as unread', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to mark notifications as unread: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get notification bell state for debugging
     */
    public function getNotificationBellState(Request $request): JsonResponse
    {
        $user = Auth::user();

        try {
            $notifications = $user->notifications()
                ->with('notification')
                ->delivered()
                ->orderBy('delivered_at', 'desc')
                ->get();

            $unreadCount = $notifications->where('read_at', null)->count();
            $totalCount = $notifications->count();
            
            $recentNotifications = $notifications->take(5)->map(function ($recipient) {
                return [
                    'id' => $recipient->id,
                    'title' => $recipient->notification->title,
                    'message' => $recipient->notification->message,
                    'type' => $recipient->notification->type,
                    'priority' => $recipient->notification->priority,
                    'delivered_at' => $recipient->delivered_at,
                    'read_at' => $recipient->read_at,
                    'is_read' => $recipient->read_at !== null,
                ];
            });

            return response()->json([
                'bell_state' => [
                    'unread_count' => $unreadCount,
                    'total_count' => $totalCount,
                    'has_unread' => $unreadCount > 0,
                    'recent_notifications' => $recentNotifications,
                ],
                'user' => $user->name,
                'debug' => true
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting notification bell state', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to get notification bell state: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system status for debugging
     */
    public function getSystemStatus(Request $request): JsonResponse
    {
        $user = Auth::user();

        try {
            $status = [
                'environment' => config('app.env'),
                'debug_mode' => config('app.debug'),
                'current_user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'membership_type' => $user->membership_type,
                    'status' => $user->status,
                ],
                'notifications' => [
                    'total_in_system' => Notification::count(),
                    'sent_today' => Notification::whereDate('sent_at', today())->count(),
                    'user_total' => $user->notifications()->count(),
                    'user_unread' => $user->notifications()->unread()->count(),
                ],
                'database' => [
                    'users_count' => User::count(),
                    'active_users' => User::where('status', 'active')->count(),
                ],
                'timestamp' => now()->toISOString(),
            ];

            return response()->json([
                'status' => $status,
                'debug' => true
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting system status', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to get system status: ' . $e->getMessage()
            ], 500);
        }
    }
}