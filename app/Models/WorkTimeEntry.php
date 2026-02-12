<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkTimeEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'instructor_id',
        'date',
        'start_time',
        'end_time',
        'hours_worked',
        'description',
        'approved',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'date' => 'date',
        'hours_worked' => 'decimal:2',
        'approved' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }
}
