<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassWaitlist extends Model
{
    protected $fillable = [
        'class_id',
        'user_id',
        'position',
        'status',
        'notified_at',
        'expires_at'
    ];
    
    protected $casts = [
        'notified_at' => 'datetime',
        'expires_at' => 'datetime'
    ];
    
    public function gymClass()
    {
        return $this->belongsTo(GymClass::class, 'class_id');
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    // Check if waitlist entry is still valid
    public function isValid()
    {
        return $this->status === 'waiting' || 
               ($this->status === 'notified' && $this->expires_at && $this->expires_at->isFuture());
    }
}