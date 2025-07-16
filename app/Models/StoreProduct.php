<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StoreProduct extends Model
{
    /** @use HasFactory<\Database\Factories\StoreProductFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'description',
        'image_url',
        'category',
        'slug',
        'is_active',
        'stock_quantity',
        'original_price',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'original_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    // Scope for active products
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope for ordered products
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }

    // Scope for category filter
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // Get route key name for URL binding
    public function getRouteKeyName()
    {
        return 'slug';
    }

    // Auto-generate slug from name
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }
}
