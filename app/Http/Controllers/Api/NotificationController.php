<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserPushToken;
use App\Models\ScheduledNotification;
use App\Models\NotificationLog;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    public function __construct(
        private PushNotificationService $pushService
    ) {}

    /**
     * Store/Update push token
     */
    public function storePushToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string|min:10',
            'platform' => 'required|in:ios,android,web'
        ]);

        $userId = Auth::id();
        
        try {
            UserPushToken::saveToken(
                $userId,
                $validated['token'],
                $validated['platform']
            );

            Log::info('Push token saved', [
                'user_id' => $userId,
                'platform' => $validated['platform'],
                'token_preview' => substr($validated['token'], 0, 20) . '...'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Push token saved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to save push token', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save push token'
            ], 500);
        }
    }

    /**
     * Delete push token
     */
    public function deletePushToken(Request $request): JsonResponse
    {
        $userId = Auth::id();
        
        try {
            $deleted = UserPushToken::where('user_id', $userId)->delete();

            Log::info('Push tokens deleted', [
                'user_id' => $userId,
                'count' => $deleted
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Push tokens deleted successfully',
                'deleted_count' => $deleted
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete push tokens', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete push tokens'
            ], 500);
        }
    }

    /**
     * Get user's push tokens
     */
    public function getUserPushTokens(Request $request): JsonResponse
    {
        $userId = Auth::id();
        
        $tokens = UserPushToken::where('user_id', $userId)
            ->select('id', 'platform', 'is_active', 'created_at', 'updated_at')
            ->get()
            ->map(function ($token) {
                return [
                    'id' => $token->id,
                    'platform' => $token->platform,
                    'is_active' => $token->is_active,
                    'token_preview' => '***' . substr($token->token, -8),
                    'created_at' => $token->created_at,
                    'updated_at' => $token->updated_at
                ];
            });

        return response()->json([
            'success' => true,
            'tokens' => $tokens
        ]);
    }

    /**
     * Schedule notification
     */
    public function scheduleNotification(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|string|unique:scheduled_notifications,id',
            'type' => 'required|in:package_expiry_week,package_expiry_2days,appointment_reminder',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
            'scheduled_for' => 'required|date|after:now',
            'user_id' => 'required|exists:users,id',
            'related_id' => 'nullable|integer',
            'data' => 'nullable|array'
        ]);

        try {
            ScheduledNotification::create($validated);

            Log::info('Notification scheduled', [
                'notification_id' => $validated['id'],
                'user_id' => $validated['user_id'],
                'type' => $validated['type'],
                'scheduled_for' => $validated['scheduled_for']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notification scheduled successfully',
                'notification_id' => $validated['id']
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to schedule notification', [
                'error' => $e->getMessage(),
                'data' => $validated
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to schedule notification'
            ], 500);
        }
    }

    /**
     * Cancel notification
     */
    public function cancelNotification(string $notificationId): JsonResponse
    {
        try {
            $notification = ScheduledNotification::find($notificationId);
            
            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            if ($notification->is_sent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel notification that has already been sent'
                ], 400);
            }

            $notification->delete();

            Log::info('Notification cancelled', [
                'notification_id' => $notificationId,
                'user_id' => $notification->user_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notification cancelled successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to cancel notification', [
                'notification_id' => $notificationId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel notification'
            ], 500);
        }
    }

    /**
     * Get user notifications
     */
    public function getUserNotifications(int $userId): JsonResponse
    {
        try {
            $notifications = ScheduledNotification::getUserPendingNotifications($userId);

            return response()->json([
                'success' => true,
                'notifications' => $notifications
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get user notifications', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get notifications'
            ], 500);
        }
    }

    /**
     * Send test notification
     */
    public function sendTestNotification(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
            'token' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id'
        ]);

        try {
            if ($validated['token']) {
                // Send to specific token
                $result = $this->pushService->sendToToken(
                    $validated['token'],
                    $validated['title'],
                    $validated['body'],
                    ['type' => 'test', 'sent_at' => now()->toISOString()]
                );

                return response()->json([
                    'success' => $result['success'],
                    'message' => $result['message'],
                    'result' => $result
                ]);
            } elseif ($validated['user_id']) {
                // Send to all user's devices
                $results = $this->pushService->sendToUser(
                    $validated['user_id'],
                    $validated['title'],
                    $validated['body'],
                    ['type' => 'test', 'sent_at' => now()->toISOString()]
                );

                $successCount = collect($results)->where('success', true)->count();
                $totalCount = count($results);

                return response()->json([
                    'success' => $successCount > 0,
                    'message' => "Sent to $successCount of $totalCount devices",
                    'results' => $results
                ]);
            } else {
                // Send to current user
                $userId = Auth::id();
                $results = $this->pushService->sendToUser(
                    $userId,
                    $validated['title'],
                    $validated['body'],
                    ['type' => 'test', 'sent_at' => now()->toISOString()]
                );

                $successCount = collect($results)->where('success', true)->count();
                $totalCount = count($results);

                return response()->json([
                    'success' => $successCount > 0,
                    'message' => "Sent to $successCount of $totalCount devices",
                    'results' => $results
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Test notification failed', [
                'error' => $e->getMessage(),
                'data' => $validated
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Test notification failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get notification logs for user
     */
    public function getUserNotificationLogs(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $limit = $request->get('limit', 50);

        try {
            $logs = NotificationLog::getUserLogs($userId, $limit);

            return response()->json([
                'success' => true,
                'logs' => $logs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get notification logs'
            ], 500);
        }
    }

    /**
     * Test push notification service connectivity
     */
    public function testConnectivity(): JsonResponse
    {
        try {
            $results = $this->pushService->testConnectivity();

            return response()->json([
                'success' => true,
                'connectivity' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connectivity test failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin: Get all scheduled notifications
     */
    public function adminGetScheduledNotifications(Request $request): JsonResponse
    {
        try {
            $query = ScheduledNotification::with('user:id,name,email')
                ->orderBy('scheduled_for');

            if ($request->has('is_sent')) {
                $query->where('is_sent', $request->boolean('is_sent'));
            }

            if ($request->has('type')) {
                $query->where('type', $request->get('type'));
            }

            $notifications = $query->paginate(50);

            return response()->json([
                'success' => true,
                'notifications' => $notifications
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get scheduled notifications'
            ], 500);
        }
    }

    /**
     * Admin: Get notification statistics
     */
    public function adminGetNotificationStats(): JsonResponse
    {
        try {
            $stats = [
                'scheduled_notifications' => [
                    'total' => ScheduledNotification::count(),
                    'pending' => ScheduledNotification::where('is_sent', false)->where('scheduled_for', '>', now())->count(),
                    'sent' => ScheduledNotification::where('is_sent', true)->count(),
                    'overdue' => ScheduledNotification::where('is_sent', false)->where('scheduled_for', '<=', now())->count(),
                ],
                'push_tokens' => [
                    'total' => UserPushToken::count(),
                    'active' => UserPushToken::where('is_active', true)->count(),
                    'by_platform' => UserPushToken::where('is_active', true)
                        ->selectRaw('platform, count(*) as count')
                        ->groupBy('platform')
                        ->pluck('count', 'platform')
                ],
                'notification_logs' => [
                    'total' => NotificationLog::count(),
                    'success_rate' => NotificationLog::where('status', 'success')->count() / max(NotificationLog::count(), 1) * 100,
                    'last_24h' => NotificationLog::where('sent_at', '>=', now()->subDay())->count()
                ]
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get notification statistics'
            ], 500);
        }
    }
}

