<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Questionnaire extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'triggers',
        'frequency_settings',
        'questions',
        'is_active',
        'created_by',
        'scheduled_send_at',
    ];

    protected $casts = [
        'triggers' => 'array',
        'frequency_settings' => 'array',
        'questions' => 'array',
        'is_active' => 'boolean',
        'scheduled_send_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(QuestionnaireResponse::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByTrigger($query, $triggerType)
    {
        return $query->whereJsonContains('triggers', $triggerType);
    }
}
