<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParentConsent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'parent_full_name',
        'father_first_name',
        'father_last_name',
        'mother_first_name',
        'mother_last_name',
        'parent_birth_date',
        'parent_id_number',
        'parent_phone',
        'parent_location',
        'parent_street',
        'parent_street_number',
        'parent_postal_code',
        'parent_email',
        'consent_accepted',
        'signature',
        'consent_text',
        'consent_version',
        'server_timestamp'
    ];

    protected $casts = [
        'parent_birth_date' => 'date',
        'consent_accepted' => 'boolean',
        'server_timestamp' => 'datetime'
    ];

    /**
     * Get the user that owns the parent consent.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get parent's full address
     */
    public function getFullAddressAttribute()
    {
        return "{$this->parent_street} {$this->parent_street_number}, {$this->parent_postal_code} {$this->parent_location}";
    }

    /**
     * Check if parent is adult (over 18)
     */
    public function isParentAdult()
    {
        return $this->parent_birth_date->age >= 18;
    }
}