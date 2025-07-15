<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'date',
        'time',
        'location',
        'image_url',
        'type',
        'details',
        'is_active',
        'max_attendees',
        'current_attendees',
    ];

    protected $casts = [
        'date' => 'date',
        'time' => 'datetime:H:i',
        'details' => 'json',
        'is_active' => 'boolean',
    ];

    public function rsvps()
    {
        return $this->hasMany(EventRSVP::class);
    }

    public function attendees()
    {
        return $this->hasMany(EventRSVP::class)->where('response', 'yes');
    }

    public function getAttendeesCountAttribute()
    {
        return $this->attendees()->count();
    }

    public function getTypeDisplayAttribute()
    {
        $types = [
            'social' => 'Κοινωνική Εκδήλωση',
            'educational' => 'Εκπαιδευτικό',
            'fitness' => 'Fitness',
            'other' => 'Άλλο'
        ];

        return $types[$this->type] ?? $this->type;
    }
}
