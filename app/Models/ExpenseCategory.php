<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'category_type',
        'parent_id',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function parent()
    {
        return $this->belongsTo(ExpenseCategory::class, 'parent_id');
    }

    public function subcategories()
    {
        return $this->hasMany(ExpenseCategory::class, 'parent_id');
    }

    public function businessExpenses()
    {
        return $this->hasMany(BusinessExpense::class, 'category_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMainCategories($query)
    {
        return $query->where('category_type', 'main');
    }

    public function scopeSubcategories($query)
    {
        return $query->where('category_type', 'subcategory');
    }

    // Accessors
    public function getFullNameAttribute()
    {
        if ($this->parent) {
            return $this->parent->name . ' > ' . $this->name;
        }
        return $this->name;
    }
}






