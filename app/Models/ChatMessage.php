<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'content',
        'sender_type',
        'is_read',
        'read_at',
        'attachment_url',
        'attachment_type'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    protected static function booted()
    {
        static::created(function ($message) {
            // Update conversation's last message timestamp
            $message->conversation->update([
                'last_message_at' => $message->created_at
            ]);

            // Update unread counts
            if ($message->sender_type === 'user') {
                $message->conversation->increment('admin_unread_count');
                
                // Send notification to admins
                $admins = \App\Models\User::where('role', 'admin')->get();
                foreach ($admins as $admin) {
                    \App\Events\ChatMessageReceived::dispatch($message, $admin, false);
                }
            } else {
                $message->conversation->increment('unread_count');
                
                // Send notification to user
                if ($message->conversation->user) {
                    \App\Events\ChatMessageReceived::dispatch($message, $message->conversation->user, true);
                }
            }
        });
    }
}
