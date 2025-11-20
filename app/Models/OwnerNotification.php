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
        'data',
        'user_id',
        'related_model_type',
        'related_model_id',
        'metadata',
        'trainer_name',
        'customer_name',
        'booking_id',
        'package_id'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'data' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the user that owns this notification
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the related model (polymorphic)
     */
    public function relatedModel()
    {
        return $this->morphTo('related_model', 'related_model_type', 'related_model_id');
    }
}
