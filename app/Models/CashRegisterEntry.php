<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashRegisterEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'amount',
        'description',
        'category',
        'user_id',
        'payment_method',
        'related_entity_id',
        'related_entity_type',
        'store_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
