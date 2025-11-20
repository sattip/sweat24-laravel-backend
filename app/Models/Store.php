<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'description',
        'email',
        'is_active',
        'color',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function cashRegisterEntries()
    {
        return $this->hasMany(CashRegisterEntry::class);
    }

    public function appointmentRequests()
    {
        return $this->hasMany(AppointmentRequest::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function businessExpenses()
    {
        return $this->hasMany(BusinessExpense::class);
    }
}
