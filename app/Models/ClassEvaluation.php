<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ClassEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'booking_id',
        'evaluation_token',
        'overall_rating',
        'instructor_rating',
        'facility_rating',
        'comments',
        'tags',
        'would_recommend',
        'is_submitted',
        'sent_at',
        'submitted_at',
        'expires_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'would_recommend' => 'boolean',
        'is_submitted' => 'boolean',
        'sent_at' => 'datetime',
        'submitted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $hidden = [
        'booking_id', // Keep booking reference hidden for anonymity
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($evaluation) {
            if (!$evaluation->evaluation_token) {
                $evaluation->evaluation_token = Str::random(32);
            }
            if (!$evaluation->expires_at) {
                $evaluation->expires_at = now()->addDays(7); // 7 days to complete
            }
        });
    }

    public function gymClass()
    {
        return $this->belongsTo(GymClass::class, 'class_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function scopePending($query)
    {
        return $query->where('is_submitted', false)
                    ->where('expires_at', '>', now());
    }

    public function scopeSubmitted($query)
    {
        return $query->where('is_submitted', true);
    }

    public function scopeExpired($query)
    {
        return $query->where('is_submitted', false)
                    ->where('expires_at', '<=', now());
    }

    public function isExpired()
    {
        return !$this->is_submitted && $this->expires_at && $this->expires_at->isPast();
    }

    public function canBeSubmitted()
    {
        return !$this->is_submitted && !$this->isExpired();
    }

    // Common feedback tags
    public static function getAvailableTags()
    {
        return [
            'excellent_instructor' => 'Εξαιρετικός εκπαιδευτής',
            'great_atmosphere' => 'Υπέροχη ατμόσφαιρα',
            'challenging_workout' => 'Προκλητική προπόνηση',
            'well_organized' => 'Καλά οργανωμένο',
            'clean_facility' => 'Καθαρές εγκαταστάσεις',
            'motivating' => 'Παρακινητικό',
            'too_crowded' => 'Πολύ συνωστισμός',
            'needs_improvement' => 'Χρειάζεται βελτίωση',
            'equipment_issues' => 'Προβλήματα εξοπλισμού',
            'timing_issues' => 'Θέματα χρονισμού',
        ];
    }
}