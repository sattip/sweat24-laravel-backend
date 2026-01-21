<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'name',
        'code',
        'address',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get only active locations
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Order by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
    }
}
