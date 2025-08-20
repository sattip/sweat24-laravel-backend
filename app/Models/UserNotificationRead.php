<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNotificationRead extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'notification_id',
        'read_at'
    ];

    protected $casts = [
        'read_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }
}