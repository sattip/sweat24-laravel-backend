<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionnaireResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'questionnaire_id',
        'user_id',
        'responses',
        'trigger_type',
        'completed_at',
        'session_id',
    ];

    protected $casts = [
        'responses' => 'array',
        'completed_at' => 'datetime',
    ];

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(Questionnaire::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByTriggerType($query, $triggerType)
    {
        return $query->where('trigger_type', $triggerType);
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at');
    }
}
