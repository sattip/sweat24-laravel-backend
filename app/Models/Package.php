<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'sessions',
        'duration',
        'status',
        'description',
        'class_type',
        'class_types',
        'time_restriction_enabled',
        'booking_start_time',
        'booking_end_time',
        // 'service_id' removed - now using many-to-many relationship
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'time_restriction_enabled' => 'boolean',
            'class_types' => 'array',
        ];
    }

    public function userPackages()
    {
        return $this->hasMany(UserPackage::class);
    }

    public function paymentInstallments()
    {
        return $this->hasMany(PaymentInstallment::class);
    }

    public function services()
    {
        return $this->belongsToMany(Service::class);
    }

    // Keep the old relationship for backward compatibility (will be removed later)
    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}