<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgeVerificationLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'birth_date',
        'calculated_age',
        'is_minor',
        'server_date',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'birth_date' => 'date',
        'server_date' => 'date',
        'is_minor' => 'boolean',
        'calculated_age' => 'integer'
    ];
}