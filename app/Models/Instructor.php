<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Instructor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'title',
        'image_url',
        'bio',
        'specialties',
        'certifications',
        'services',
        'email',
        'phone',
        'hourly_rate',
        'monthly_bonus',
        'commission_rate',
        'contract_type',
        'status',
        'join_date',
        'total_revenue',
        'completed_sessions',
        'display_order',
        'experience',
    ];

    protected function casts(): array
    {
        return [
            'specialties' => 'json',
            'certifications' => 'json',
            'services' => 'json',
            'hourly_rate' => 'decimal:2',
            'monthly_bonus' => 'decimal:2',
            'commission_rate' => 'decimal:4',
            'total_revenue' => 'decimal:2',
            'join_date' => 'date',
        ];
    }

    public function workTimeEntries()
    {
        return $this->hasMany(WorkTimeEntry::class);
    }

    public function payrollAgreements()
    {
        return $this->hasMany(PayrollAgreement::class);
    }
}