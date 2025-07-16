<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageNotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_package_id',
        'user_id',
        'notification_type',
        'channel',
        'sent_successfully',
        'error_message',
        'days_until_expiry',
    ];

    protected function casts(): array
    {
        return [
            'sent_successfully' => 'boolean',
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

    // Scope for successful notifications
    public function scopeSuccessful($query)
    {
        return $query->where('sent_successfully', true);
    }

    // Scope for failed notifications
    public function scopeFailed($query)
    {
        return $query->where('sent_successfully', false);
    }

    // Scope by notification type
    public function scopeByType($query, $type)
    {
        return $query->where('notification_type', $type);
    }

    // Scope by channel
    public function scopeByChannel($query, $channel)
    {
        return $query->where('channel', $channel);
    }
}