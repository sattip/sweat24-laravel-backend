<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FitnessLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'level',
        'assessment_date',
        'assessed_by',
        'notes',
    ];

    protected $casts = [
        'assessment_date' => 'date',
    ];

    /**
     * Get the user that this fitness level belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user (trainer) who assessed this fitness level.
     */
    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    /**
     * Get the label for the fitness level.
     */
    public function getLevelLabelAttribute(): string
    {
        $labels = [
            'beginner' => 'Πολύ Αρχάριος',
            'intermediate' => 'Ενδιάμεσος',
            'advanced' => 'Προχωρημένος',
            'elite' => 'Αθλητικό / Performance Level',
        ];

        return $labels[$this->level] ?? $this->level;
    }

    /**
     * Get the color for the fitness level badge.
     */
    public function getLevelColorAttribute(): string
    {
        $colors = [
            'beginner' => 'green',
            'intermediate' => 'yellow',
            'advanced' => 'blue',
            'elite' => 'purple',
        ];

        return $colors[$this->level] ?? 'gray';
    }
}
