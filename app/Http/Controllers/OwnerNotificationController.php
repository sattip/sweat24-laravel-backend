<?php

namespace App\Http\Controllers;

use App\Models\OwnerNotification;
use Illuminate\Http\Request;

class OwnerNotificationController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'nullable|string|max:50',
            'priority' => 'nullable|in:low,medium,high',
            'user_id' => 'nullable|exists:users,id',
            'metadata' => 'nullable|array',
            'trainer_name' => 'nullable|string',
            'customer_name' => 'nullable|string',
            'booking_id' => 'nullable|string',
            'package_id' => 'nullable|string',
        ]);

        $notification = OwnerNotification::create([
            'title' => $validated['title'],
            'message' => $validated['message'],
            'type' => $validated['type'] ?? 'info',
            'priority' => $validated['priority'] ?? 'medium',
            'user_id' => $validated['user_id'] ?? null,
            'metadata' => $validated['metadata'] ?? null,
            'trainer_name' => $validated['trainer_name'] ?? null,
            'customer_name' => $validated['customer_name'] ?? null,
            'booking_id' => $validated['booking_id'] ?? null,
            'package_id' => $validated['package_id'] ?? null,
            'is_read' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Η ειδοποίηση δημιουργήθηκε επιτυχώς',
            'data' => $notification
        ], 201);
    }

    public function index(Request $request)
    {
        $query = OwnerNotification::query();

        // Filter by current admin user (show only notifications for this admin)
        $query->where(function ($q) {
            $q->where('user_id', auth()->id())
              ->orWhereNull('user_id'); // Also show notifications without specific user
        });

        // Filter by read status if provided
        if ($request->has('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }

        // Get notifications ordered by creation date
        $notifications = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }
    
    public function markAsRead($id)
    {
        $notification = OwnerNotification::findOrFail($id);
        $notification->update(['is_read' => true]);
        
        return response()->json([
            'success' => true,
            'message' => 'Η ειδοποίηση σημειώθηκε ως αναγνωσμένη'
        ]);
    }
    
    public function markAllAsRead()
    {
        OwnerNotification::where('is_read', false)
            ->update(['is_read' => true]);
        
        return response()->json([
            'success' => true,
            'message' => 'Όλες οι ειδοποιήσεις σημειώθηκαν ως αναγνωσμένες'
        ]);
    }
    
    public function delete($id)
    {
        $notification = OwnerNotification::findOrFail($id);
        $notification->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Η ειδοποίηση διαγράφηκε επιτυχώς'
        ]);
    }
}