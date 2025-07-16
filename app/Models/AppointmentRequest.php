<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'specialized_service_id',
        'instructor_id',
        'client_name',
        'client_email',
        'client_phone',
        'preferred_time_slots',
        'notes',
        'status',
        'confirmed_date',
        'confirmed_time',
    ];

    protected $casts = [
        'preferred_time_slots' => 'json',
        'confirmed_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function specializedService()
    {
        return $this->belongsTo(SpecializedService::class);
    }

    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }
}
