<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'customer_name',
        'customer_email',
        'class_name',
        'instructor',
        'date',
        'time',
        'status',
        'type',
        'attended',
        'booking_time',
        'location',
        'avatar',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'time' => 'datetime',
            'booking_time' => 'datetime',
            'attended' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}