<?php

namespace App\Events;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $recipient;
    public $isForUser; // true if message is for user, false if for admin

    public function __construct(ChatMessage $message, User $recipient, bool $isForUser = true)
    {
        $this->message = $message;
        $this->recipient = $recipient;
        $this->isForUser = $isForUser;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->recipient->id),
        ];
    }
    
    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        // Load the conversation to get the updated unread count
        $this->message->load('conversation');
        
        return [
            'message' => $this->message->toArray(),
            'unread_count' => $this->isForUser ? 
                $this->message->conversation->unread_count : 
                $this->message->conversation->admin_unread_count,
            'conversation_id' => $this->message->conversation_id
        ];
    }
} 