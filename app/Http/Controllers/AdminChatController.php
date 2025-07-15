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
            'lastMessage'
        ])
        ->where('status', $status)
        ->orderBy('last_message_at', 'desc')
        ->get()
        ->map(function ($conversation) {
            return [
                'id' => $conversation->id,
                'user' => $conversation->user,
                'status' => $conversation->status,
                'last_message_at' => $conversation->last_message_at,
                'admin_unread_count' => $conversation->admin_unread_count,
                'last_message' => $conversation->lastMessage,
                'messages' => $conversation->messages()->with('sender:id,name,avatar')->get()
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
    
    public function markAsRead($conversationId)
    {
        $conversation = ChatConversation::findOrFail($conversationId);
        $conversation->markAsRead('admin');
        
        return response()->json(['message' => 'Messages marked as read']);
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
