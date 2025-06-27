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
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }
}
