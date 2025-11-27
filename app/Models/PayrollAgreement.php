<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollAgreement extends Model
{
    const TYPE_BONUS = 'bonus';
    const TYPE_DEDUCTION = 'deduction';
    const TYPE_SPECIAL_RATE = 'special_rate';
    const TYPE_HOURLY_RATE = 'hourly_rate';

    protected $fillable = [
        'instructor_id',
        'instructor_name',
        'description',
        'type',
        'amount',
        'is_recurring',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_recurring' => 'boolean',
            'is_active' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_BONUS => 'Bonus',
            self::TYPE_DEDUCTION => 'Αφαίρεση',
            self::TYPE_SPECIAL_RATE => 'Ειδική Τιμή',
            self::TYPE_HOURLY_RATE => 'Ωριαία Αμοιβή',
        ];
    }
}
