<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentInstallment extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'customer_name',
        'package_id',
        'package_name',
        'installment_number',
        'total_installments',
        'amount',
        'due_date',
        'paid_date',
        'payment_method',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'paid_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }
}
