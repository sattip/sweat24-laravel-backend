<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'package_id',
        'name',
        'assigned_date',
        'expiry_date',
        'remaining_sessions',
        'total_sessions',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'assigned_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}