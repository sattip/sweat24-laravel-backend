<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'category',
        'subcategory',
        'description',
        'amount',
        'date',
        'vendor',
        'receipt',
        'payment_method',
        'approved',
        'approved_by',
        'notes',
        'store_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
            'approved' => 'boolean',
        ];
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function expenseCategory()
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }
}
