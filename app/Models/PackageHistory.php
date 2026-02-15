<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageHistory extends Model
{
    use HasFactory;

    protected $table = 'package_history';

    protected $fillable = [
        'user_package_id',
        'user_id',
        'action',
        'previous_status',
        'new_status',
        'sessions_before',
        'sessions_after',
        'expiry_date_before',
        'expiry_date_after',
        'notes',
        'performed_by',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date_before' => 'date',
            'expiry_date_after' => 'date',
            'notes' => 'array',
        ];
    }

    public function userPackage()
    {
        return $this->belongsTo(UserPackage::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    // Scope for filtering by action
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    // Scope for recent history
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}