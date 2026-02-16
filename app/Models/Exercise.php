<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_en',
        'name_gr',
        'muscle_group',
        'category',
        'equipment',
        'difficulty_level',
        'description',
        'video_url',
        'image_url',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'equipment' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user who created this exercise.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the training exercises that use this exercise.
     */
    public function trainingExercises(): HasMany
    {
        return $this->hasMany(TrainingExercise::class);
    }

    /**
     * Scope to filter by muscle group.
     */
    public function scopeByMuscleGroup($query, $muscleGroup)
    {
        return $query->where('muscle_group', $muscleGroup);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to filter by difficulty level.
     */
    public function scopeByDifficulty($query, $difficulty)
    {
        return $query->where('difficulty_level', $difficulty);
    }

    /**
     * Scope to filter by equipment.
     */
    public function scopeByEquipment($query, $equipment)
    {
        return $query->whereJsonContains('equipment', $equipment);
    }

    /**
     * Scope to get only active exercises.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Search exercises by name (English or Greek).
     */
    public function scopeSearch($query, $term)
    {
        // Escape LIKE special characters to prevent pattern manipulation
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);

        return $query->where(function ($query) use ($escaped) {
            $query->where('name_en', 'like', '%' . $escaped . '%')
                  ->orWhere('name_gr', 'like', '%' . $escaped . '%');
        });
    }
}
