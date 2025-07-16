<?php

namespace App\Http\Controllers;

use App\Models\OwnerNotification;
use Illuminate\Http\Request;

class OwnerNotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = OwnerNotification::query();
        
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