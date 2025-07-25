<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventRSVP extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'guest_name',
        'guest_email',
        'response',
        'notes',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
