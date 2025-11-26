<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegisterSession extends Model
{
    protected $fillable = [
        'store_id',
        'opened_by',
        'closed_by',
        'opening_amount',
        'expected_closing_amount',
        'actual_closing_amount',
        'discrepancy',
        'opening_notes',
        'closing_notes',
        'status',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'opening_amount' => 'decimal:2',
        'expected_closing_amount' => 'decimal:2',
        'actual_closing_amount' => 'decimal:2',
        'discrepancy' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function openedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(CashRegisterEntry::class, 'store_id', 'store_id')
            ->where('created_at', '>=', $this->opened_at)
            ->when($this->closed_at, function ($query) {
                return $query->where('created_at', '<=', $this->closed_at);
            });
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function calculateExpectedClosing(): float
    {
        $entriesSum = CashRegisterEntry::where('store_id', $this->store_id)
            ->where('created_at', '>=', $this->opened_at)
            ->selectRaw("SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) as total")
            ->value('total') ?? 0;

        return $this->opening_amount + $entriesSum;
    }
}
