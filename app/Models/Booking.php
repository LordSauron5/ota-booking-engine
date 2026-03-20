<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    protected $fillable = [
        'reference',
        'user_id',
        'session_token',
        'session_token_expires_at',
        'unit_id',
        'check_in',
        'check_out',
        'nights',
        'quantity',
        'guests',
        'price_per_unit',
        'base_price',
        'tax_amount',
        'total_price',
        'status',
        'channel_manager_ref',
    ];

    protected $casts = [
        'guests'                   => 'array',
        'session_token_expires_at' => 'datetime',
        'check_in'                 => 'date',
        'check_out'                => 'date',
        'price_per_unit'           => 'decimal:2',
        'base_price'               => 'decimal:2',
        'tax_amount'               => 'decimal:2',
        'total_price'              => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}