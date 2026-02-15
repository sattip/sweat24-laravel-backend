<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Events\ChatMessageReceived;
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
        ->get();
        
        // Collect conversations that need unread count updates
        $conversationsToUpdate = [];
        
        $conversationsData = $conversations->map(function ($conversation) use (&$conversationsToUpdate) {
            // Calculate actual unread count for admin
            $adminUnreadCount = $conversation->messages
                ->where('sender_type', 'user')
                ->where('is_read', false)
                ->count();
            
            // Track conversations that need updating
            if ($adminUnreadCount !== $conversation->admin_unread_count) {
                $conversationsToUpdate[$conversation->id] = $adminUnreadCount;
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
        
        // Perform bulk update outside the loop to avoid N+1 queries
        foreach ($conversationsToUpdate as $conversationId => $unreadCount) {
            ChatConversation::where('id', $conversationId)
                ->update(['admin_unread_count' => $unreadCount]);
        }
        
        return response()->json($conversationsData);
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
        
        // Broadcast the message to the user
        $conversation = ChatConversation::find($request->conversation_id);
        if ($conversation && $conversation->user) {
            event(new ChatMessageReceived($message, $conversation->user, true));
        }
        
        // Update conversation's last message timestamp and increment unread count
        $conversation->update([
            'last_message_at' => now(),
            'unread_count' => $conversation->unread_count + 1
        ]);
        
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
        
        // Use firstOrCreate for atomic operation to prevent race condition
        $conversation = ChatConversation::firstOrCreate(
            ['user_id' => $request->user_id],
            [
                'status' => 'active',
                'last_message_at' => now(),
                'admin_unread_count' => 0,
                'unread_count' => 0  // Set to 0 because message creation will increment it
            ]
        );
        
        // If an existing conversation was resolved or archived, reactivate it
        if (!$conversation->wasRecentlyCreated && $conversation->status !== 'active') {
            $conversation->update(['status' => 'active']);
        }
        
        // Create the initial message from admin
        $message = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $admin->id,
            'sender_type' => 'admin',
            'content' => $request->initial_message,
            'is_read' => false
        ]);
        
        // Update unread count for user (admin sent a message)
        $conversation->increment('unread_count');
        
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
