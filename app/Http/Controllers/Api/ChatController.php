<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    /**
     * Get or create a conversation for the authenticated user
     */
    public function getConversation()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }
        $userId = $user->id;
        
        $conversation = ChatConversation::firstOrCreate(
            ['user_id' => $userId],
            ['status' => 'active']
        );
        
        // Load messages with sender info
        $conversation->load(['messages' => function($query) {
            $query->with('sender:id,name,avatar')
                  ->orderBy('created_at', 'asc');
        }, 'user:id,name,email,avatar']);
        
        return response()->json([
            'conversation' => $conversation
        ]);
    }
    
    /**
     * Send a message in a conversation
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:5000',
            'attachment_url' => 'nullable|url',
            'attachment_type' => 'nullable|in:image,file'
        ]);
        
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }
        
        $userId = $user->id;
        $senderType = $user->role === 'admin' ? 'admin' : 'user';
        
        // Get or create conversation
        $conversation = ChatConversation::firstOrCreate(
            ['user_id' => $userId],
            ['status' => 'active']
        );
        
        // Create message
        $message = $conversation->messages()->create([
            'sender_id' => $userId,
            'sender_type' => $senderType,
            'content' => $request->content,
            'attachment_url' => $request->attachment_url,
            'attachment_type' => $request->attachment_type,
        ]);
        
        $message->load('sender:id,name,avatar');
        
        return response()->json([
            'message' => $message
        ], 201);
    }
    
    /**
     * Mark messages as read
     */
    public function markAsRead(Request $request, $conversationId)
    {
        $conversation = ChatConversation::findOrFail($conversationId);
        
        // Check if user has access to this conversation
        $user = Auth::user();
        if (!$user->isAdmin() && $conversation->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $senderType = $user->isAdmin() ? 'admin' : 'user';
        $conversation->markAsRead($senderType);
        
        return response()->json([
            'message' => 'Messages marked as read'
        ]);
    }
    
    /**
     * Get all conversations (admin only)
     */
    public function getAllConversations(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $conversations = ChatConversation::with([
            'user:id,name,email,avatar',
            'lastMessage'
        ])
        ->where('status', $request->status ?? 'active')
        ->orderBy('last_message_at', 'desc')
        ->paginate(20);
        
        return response()->json($conversations);
    }
    
    /**
     * Update conversation status (admin only)
     */
    public function updateStatus(Request $request, $conversationId)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $request->validate([
            'status' => 'required|in:active,resolved,archived'
        ]);
        
        $conversation = ChatConversation::findOrFail($conversationId);
        $conversation->update(['status' => $request->status]);
        
        return response()->json([
            'message' => 'Status updated successfully',
            'conversation' => $conversation
        ]);
    }
}
