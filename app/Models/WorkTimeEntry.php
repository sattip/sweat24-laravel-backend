<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkTimeEntry extends Model
{
    protected $fillable = [
        'instructor_id',
        'date',
        'start_time',
        'end_time',
        'duration',
        'notes',
        'status'
    ];
    
    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime'
    ];
    
    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }
}
