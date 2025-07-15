<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OwnerNotification extends Model
{
    protected $fillable = [
        'title',
        'message',
        'type',
        'priority',
        'is_read',
        'data'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
