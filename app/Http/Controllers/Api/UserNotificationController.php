<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\UserNotificationRead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserNotificationController extends Controller
{
    /**
     * Get user's notifications with read/unread status
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $perPage = $request->input('per_page', 20);
        $type = $request->input('type'); // Filter by type
        $unreadOnly = $request->boolean('unread_only', false);
        
        // Get notifications that are either:
        // 1. Targeted to this specific user (via filters)
        // 2. Broadcast to all users (no specific filters or empty filters)
        $query = Notification::where('status', 'sent')
            ->where(function($q) use ($user) {
                // Notifications specifically for this user
                $q->whereJsonContains('filters->inline->user_ids', $user->id)
                  // Or notifications for user's membership type
                  ->orWhereJsonContains('filters->inline->membership_types', $user->membership_type)
                  // Or broadcast notifications (no filters or empty filters)
                  ->orWhereNull('filters')
                  ->orWhere('filters', '{}')
                  ->orWhere('filters', '[]');
            });
        
        // Filter by type if provided
        if ($type) {
            $query->where('type', $type);
        }
        
        // Get notifications with read status
        $notifications = $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
        
        // Add read status and format response
        $notifications->getCollection()->transform(function ($notification) use ($user) {
            $userRead = UserNotificationRead::where('user_id', $user->id)
                ->where('notification_id', $notification->id)
                ->first();
            
            return [
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
                'type' => $notification->type,
                'priority' => $notification->priority,
                'created_at' => $notification->created_at,
                'is_read' => $userRead && $userRead->read_at !== null,
                'read_at' => $userRead ? $userRead->read_at : null,
                'type_label' => $notification->getTypeLabel(),
                'type_icon' => $notification->getTypeIcon(),
                'type_color' => $notification->getTypeColor(),
            ];
        });
        
        // If unread_only, filter the collection
        if ($unreadOnly) {
            $notifications->setCollection(
                $notifications->getCollection()->filter(function ($item) {
                    return !$item['is_read'];
                })->values()
            );
        }
        
        // Calculate unread count
        $unreadCount = Notification::where('status', 'sent')
            ->where(function($q) use ($user) {
                $q->whereJsonContains('filters->inline->user_ids', $user->id)
                  ->orWhereJsonContains('filters->inline->membership_types', $user->membership_type)
                  ->orWhereNull('filters')
                  ->orWhere('filters', '{}')
                  ->orWhere('filters', '[]');
            })
            ->whereDoesntHave('userReads', function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->whereNotNull('read_at');
            })
            ->count();
        
        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }
    
    /**
     * Mark a single notification as read
     */
    public function markAsRead($notificationId)
    {
        $user = Auth::user();
        
        // Check if notification exists
        $notification = Notification::find($notificationId);
        if (!$notification) {
            return response()->json(['error' => 'Notification not found'], 404);
        }
        
        // Create or update the read record
        $userRead = UserNotificationRead::updateOrCreate(
            [
                'user_id' => $user->id,
                'notification_id' => $notificationId
            ],
            [
                'read_at' => now()
            ]
        );
        
        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
            'read_at' => $userRead->read_at
        ]);
    }
    
    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        
        // Get all relevant notifications for this user
        $notificationIds = Notification::where('status', 'sent')
            ->where(function($q) use ($user) {
                $q->whereJsonContains('filters->inline->user_ids', $user->id)
                  ->orWhereJsonContains('filters->inline->membership_types', $user->membership_type)
                  ->orWhereNull('filters')
                  ->orWhere('filters', '{}')
                  ->orWhere('filters', '[]');
            })
            ->pluck('id');
        
        // Mark all as read in a transaction
        DB::transaction(function () use ($user, $notificationIds) {
            foreach ($notificationIds as $notificationId) {
                UserNotificationRead::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'notification_id' => $notificationId
                    ],
                    [
                        'read_at' => now()
                    ]
                );
            }
        });
        
        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
            'count' => count($notificationIds)
        ]);
    }
    
    /**
     * Get unread notification count
     */
    public function getUnreadCount()
    {
        $user = Auth::user();
        
        $unreadCount = Notification::where('status', 'sent')
            ->where(function($q) use ($user) {
                $q->whereJsonContains('filters->inline->user_ids', $user->id)
                  ->orWhereJsonContains('filters->inline->membership_types', $user->membership_type)
                  ->orWhereNull('filters')
                  ->orWhere('filters', '{}')
                  ->orWhere('filters', '[]');
            })
            ->whereDoesntHave('userReads', function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->whereNotNull('read_at');
            })
            ->count();
        
        return response()->json([
            'unread_count' => $unreadCount
        ]);
    }
    
    /**
     * Delete a notification for the user (soft delete - just marks as deleted for this user)
     */
    public function delete($notificationId)
    {
        $user = Auth::user();
        
        // We don't actually delete the notification, just mark it as "deleted" for this user
        // This would require adding a 'deleted_at' field to user_notification_reads
        // For now, we'll just mark it as read
        $this->markAsRead($notificationId);
        
        return response()->json([
            'success' => true,
            'message' => 'Notification removed'
        ]);
    }
}