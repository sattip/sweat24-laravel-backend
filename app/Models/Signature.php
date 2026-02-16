<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Signature extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'signature_data',
        'ip_address',
        'signed_at',
        'document_type',
        'document_version'
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the signature.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
