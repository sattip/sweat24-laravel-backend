<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GymClass extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'instructor_id',
        'date',
        'time',
        'duration',
        'max_participants',
        'current_participants',
        'location',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'time' => 'datetime',
        ];
    }

    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }
}