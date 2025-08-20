<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminChatController extends Controller
{
    public function getConversations(Request $request)
    {
        $status = $request->input('status', 'active');
        
        $conversations = ChatConversation::with([
            'user:id,name,email,avatar',
            'lastMessage',
            'messages' => function($query) {
                $query->with('sender:id,name,avatar');
            }
        ])
        ->where('status', $status)
        ->orderBy('last_message_at', 'desc')
        ->get()
        ->map(function ($conversation) {
            // Calculate actual unread count for admin
            $adminUnreadCount = $conversation->messages
                ->where('sender_type', 'user')
                ->where('is_read', false)
                ->count();
            
            // Update the database if count is different
            if ($adminUnreadCount !== $conversation->admin_unread_count) {
                $conversation->update(['admin_unread_count' => $adminUnreadCount]);
            }
            
            return [
                'id' => $conversation->id,
                'user' => $conversation->user,
                'status' => $conversation->status,
                'last_message_at' => $conversation->last_message_at,
                'admin_unread_count' => $adminUnreadCount,
                'last_message' => $conversation->lastMessage,
                'messages' => $conversation->messages
            ];
        });
        
        return response()->json($conversations);
    }
    
    public function sendMessage(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:chat_conversations,id',
            'content' => 'required|string|max:5000'
        ]);
        
        $admin = Auth::user();
        
        $message = ChatMessage::create([
            'conversation_id' => $request->conversation_id,
            'sender_id' => $admin->id,
            'sender_type' => 'admin',
            'content' => $request->content
        ]);
        
        $message->load('sender:id,name,avatar');
        
        return response()->json(['message' => $message]);
    }
    
    /**
     * Create a new conversation initiated by admin
     */
    public function createConversation(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'initial_message' => 'required|string|max:5000'
        ]);
        
        $admin = Auth::user();
        
        // Check if conversation already exists
        $conversation = ChatConversation::where('user_id', $request->user_id)->first();
        
        if (!$conversation) {
            // Create new conversation
            $conversation = ChatConversation::create([
                'user_id' => $request->user_id,
                'status' => 'active',
                'last_message_at' => now(),
                'admin_unread_count' => 0,
                'unread_count' => 1
            ]);
        }
        
        // Create the initial message from admin
        $message = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $admin->id,
            'sender_type' => 'admin',
            'content' => $request->initial_message,
            'is_read' => false
        ]);
        
        // Load necessary relationships
        $conversation->load([
            'user:id,name,email,avatar',
            'messages' => function($query) {
                $query->with('sender:id,name,avatar');
            }
        ]);
        
        return response()->json([
            'success' => true,
            'conversation' => [
                'id' => $conversation->id,
                'user' => $conversation->user,
                'messages' => $conversation->messages,
                'status' => $conversation->status,
                'last_message_at' => $conversation->last_message_at,
                'admin_unread_count' => 0,
                'user_unread_count' => $conversation->unread_count
            ]
        ], 201);
    }
    
    public function markAsRead($conversationId)
    {
        $conversation = ChatConversation::findOrFail($conversationId);
        
        // Mark all user messages in this conversation as read by admin
        $updatedCount = $conversation->messages()
            ->where('sender_type', 'user')
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);
        
        // Reset admin unread count
        $conversation->update(['admin_unread_count' => 0]);
        
        return response()->json([
            'success' => true,
            'message' => 'Messages marked as read',
            'updated_count' => $updatedCount
        ]);
    }
    
    public function updateStatus(Request $request, $conversationId)
    {
        $request->validate([
            'status' => 'required|in:active,resolved,archived'
        ]);
        
        $conversation = ChatConversation::findOrFail($conversationId);
        $conversation->update(['status' => $request->status]);
        
        return response()->json(['message' => 'Status updated']);
    }
}
