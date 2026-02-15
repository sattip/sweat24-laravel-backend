<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ProgressPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'image_path',
        'caption',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    /**
     * Get the user that owns the progress photo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the full URL for the image
     */
    public function getImageUrlAttribute()
    {
        if ($this->image_path) {
            return url('storage/' . $this->image_path);
        }
        return null;
    }

    /**
     * Get formatted date in Greek
     */
    public function getFormattedDateAttribute()
    {
        Carbon::setLocale('el');
        return $this->uploaded_at->translatedFormat('j F Y');
    }

    /**
     * Format for API response
     */
    public function toApiArray()
    {
        return [
            'id' => $this->id,
            'imageUrl' => $this->image_url,
            'date' => $this->formatted_date,
            'caption' => $this->caption,
        ];
    }
}