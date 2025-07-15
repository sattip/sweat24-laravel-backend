<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatConversation extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'last_message_at',
        'unread_count',
        'admin_unread_count'
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'unread_count' => 'integer',
        'admin_unread_count' => 'integer'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }

    public function lastMessage()
    {
        return $this->hasOne(ChatMessage::class, 'conversation_id')->latest();
    }

    public function markAsRead($senderType = 'admin')
    {
        if ($senderType === 'admin') {
            $this->messages()->where('sender_type', 'user')
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now()
                ]);
            $this->update(['admin_unread_count' => 0]);
        } else {
            $this->messages()->where('sender_type', 'admin')
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now()
                ]);
            $this->update(['unread_count' => 0]);
        }
    }
}
