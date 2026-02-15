<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class NotificationTestController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
        
        // Only allow in development mode
        if (config('app.env') !== 'local') {
            abort(404, 'Testing tools only available in development mode');
        }
    }

    /**
     * Show the notification testing page
     */
    public function index()
    {
        return view('admin.notifications.test');
    }

    /**
     * Send test in-app notification
     */
    public function sendTestInApp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'target_user_id' => 'nullable|exists:users,id',
            'message' => 'nullable|string|max:500',
        ]);

        $targetUser = $validated['target_user_id'] 
            ? User::find($validated['target_user_id']) 
            : User::where('membership_type', 'Admin')->first();

        if (!$targetUser) {
            return response()->json(['error' => 'No target user found'], 400);
        }

        $message = $validated['message'] ?? 'This is a test in-app notification sent at ' . now()->format('Y-m-d H:i:s');

        $notification = $this->notificationService->createNotification([
            'title' => '[TEST] In-App Notification',
            'message' => $message,
            'type' => 'info',
            'priority' => 'medium',
            'channels' => ['in_app'],
            'filters' => [
                'inline' => [
                    'user_ids' => [$targetUser->id]
                ]
            ],
            'status' => 'sent'
        ]);

        Log::info('Test in-app notification sent', [
            'notification_id' => $notification->id,
            'target_user' => $targetUser->name,
            'message' => $message
        ]);

        return response()->json([
            'message' => 'Test in-app notification sent successfully',
            'notification' => $notification,
            'target_user' => $targetUser->name
        ]);
    }

    /**
     * Send test email notification
     */
    public function sendTestEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'target_user_id' => 'nullable|exists:users,id',
            'message' => 'nullable|string|max:500',
        ]);

        $targetUser = $validated['target_user_id'] 
            ? User::find($validated['target_user_id']) 
            : User::where('membership_type', 'Admin')->first();

        if (!$targetUser) {
            return response()->json(['error' => 'No target user found'], 400);
        }

        $message = $validated['message'] ?? 'This is a test email notification sent at ' . now()->format('Y-m-d H:i:s');

        $notification = $this->notificationService->createNotification([
            'title' => '[TEST] Email Notification',
            'message' => $message,
            'type' => 'info',
            'priority' => 'medium',
            'channels' => ['email'],
            'filters' => [
                'inline' => [
                    'user_ids' => [$targetUser->id]
                ]
            ],
            'status' => 'sent'
        ]);

        Log::info('Test email notification sent', [
            'notification_id' => $notification->id,
            'target_user' => $targetUser->name,
            'email' => $targetUser->email,
            'message' => $message
        ]);

        return response()->json([
            'message' => 'Test email notification sent successfully (check logs for actual delivery)',
            'notification' => $notification,
            'target_user' => $targetUser->name,
            'target_email' => $targetUser->email
        ]);
    }

    /**
     * Send test SMS notification
     */
    public function sendTestSMS(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'target_user_id' => 'nullable|exists:users,id',
            'message' => 'nullable|string|max:160',
        ]);

        $targetUser = $validated['target_user_id'] 
            ? User::find($validated['target_user_id']) 
            : User::where('membership_type', 'Admin')->first();

        if (!$targetUser) {
            return response()->json(['error' => 'No target user found'], 400);
        }

        $message = $validated['message'] ?? 'TEST SMS: ' . now()->format('Y-m-d H:i:s');

        $notification = $this->notificationService->createNotification([
            'title' => '[TEST] SMS Notification',
            'message' => $message,
            'type' => 'info',
            'priority' => 'medium',
            'channels' => ['sms'],
            'filters' => [
                'inline' => [
                    'user_ids' => [$targetUser->id]
                ]
            ],
            'status' => 'sent'
        ]);

        Log::info('Test SMS notification sent', [
            'notification_id' => $notification->id,
            'target_user' => $targetUser->name,
            'message' => $message
        ]);

        return response()->json([
            'message' => 'Test SMS notification sent successfully (check logs for actual delivery)',
            'notification' => $notification,
            'target_user' => $targetUser->name
        ]);
    }

    /**
     * Create bulk notification for all users
     */
    public function sendBulkNotification(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:500',
            'type' => 'nullable|in:info,warning,success,error',
            'channels' => 'nullable|array',
            'channels.*' => 'in:in_app,email,sms',
            'user_type' => 'nullable|in:all,active,admin,member'
        ]);

        $title = '[BULK TEST] ' . $validated['title'];
        $message = $validated['message'] . ' (Test sent at ' . now()->format('Y-m-d H:i:s') . ')';
        $type = $validated['type'] ?? 'info';
        $channels = $validated['channels'] ?? ['in_app'];
        $userType = $validated['user_type'] ?? 'all';

        // Build filters based on user type
        $filters = [];
        if ($userType !== 'all') {
            $filters['inline'] = [];
            switch ($userType) {
                case 'active':
                    $filters['inline']['status'] = 'active';
                    break;
                case 'admin':
                    $filters['inline']['membership_type'] = 'Admin';
                    break;
                case 'member':
                    $filters['inline']['membership_type'] = 'Member';
                    break;
            }
        }

        $notification = $this->notificationService->createNotification([
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'priority' => 'medium',
            'channels' => $channels,
            'filters' => $filters,
            'status' => 'sent'
        ]);

        Log::info('Bulk test notification sent', [
            'notification_id' => $notification->id,
            'user_type' => $userType,
            'channels' => $channels,
            'title' => $title
        ]);

        return response()->json([
            'message' => 'Bulk test notification sent successfully',
            'notification' => $notification,
            'user_type' => $userType,
            'channels' => $channels
        ]);
    }

    /**
     * Create targeted notification for specific group
     */
    public function sendTargetedNotification(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:500',
            'target_group' => 'required|in:recent_members,expiring_packages,inactive_users',
            'channels' => 'nullable|array',
            'channels.*' => 'in:in_app,email,sms',
        ]);

        $title = '[TARGETED TEST] ' . $validated['title'];
        $message = $validated['message'] . ' (Test sent at ' . now()->format('Y-m-d H:i:s') . ')';
        $channels = $validated['channels'] ?? ['in_app'];
        $targetGroup = $validated['target_group'];

        // Build filters based on target group
        $filters = ['inline' => []];
        switch ($targetGroup) {
            case 'recent_members':
                $filters['inline']['created_after'] = now()->subDays(7)->format('Y-m-d');
                break;
            case 'expiring_packages':
                // This would require custom logic in the NotificationFilter model
                $filters['inline']['has_expiring_packages'] = true;
                break;
            case 'inactive_users':
                $filters['inline']['last_activity_before'] = now()->subDays(30)->format('Y-m-d');
                break;
        }

        $notification = $this->notificationService->createNotification([
            'title' => $title,
            'message' => $message,
            'type' => 'info',
            'priority' => 'medium',
            'channels' => $channels,
            'filters' => $filters,
            'status' => 'sent'
        ]);

        Log::info('Targeted test notification sent', [
            'notification_id' => $notification->id,
            'target_group' => $targetGroup,
            'channels' => $channels,
            'title' => $title
        ]);

        return response()->json([
            'message' => 'Targeted test notification sent successfully',
            'notification' => $notification,
            'target_group' => $targetGroup,
            'channels' => $channels
        ]);
    }

    /**
     * Get list of users for targeting
     */
    public function getUsers(): JsonResponse
    {
        $users = User::select('id', 'name', 'email', 'membership_type', 'status')
            ->orderBy('name')
            ->take(50)
            ->get();

        return response()->json([
            'users' => $users
        ]);
    }

    /**
     * Clear all test notifications
     */
    public function clearTestNotifications(): JsonResponse
    {
        $deletedCount = Notification::where('title', 'like', '%[TEST]%')
            ->orWhere('title', 'like', '%[BULK TEST]%')
            ->orWhere('title', 'like', '%[TARGETED TEST]%')
            ->delete();

        Log::info('Test notifications cleared', [
            'deleted_count' => $deletedCount
        ]);

        return response()->json([
            'message' => "Cleared {$deletedCount} test notifications",
            'deleted_count' => $deletedCount
        ]);
    }
}