<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'sessions',
        'duration',
        'type',
        'status',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function userPackages()
    {
        return $this->hasMany(UserPackage::class);
    }

    public function paymentInstallments()
    {
        return $this->hasMany(PaymentInstallment::class);
    }
}