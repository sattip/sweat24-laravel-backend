<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SpecializedService extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function appointmentRequests()
    {
        return $this->hasMany(AppointmentRequest::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($service) {
            if (!$service->slug) {
                $service->slug = Str::slug($service->name);
            }
        });
    }
}
