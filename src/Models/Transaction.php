<?php

namespace Khumam\Midtrans\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Khumam\Midtrans\Models\TransactionResponse;

class Transaction extends Model
{
    protected $table = 'midtrans_transactions';

    protected $guarded = [];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'ends_at' => 'datetime',
    ];

    public function billable(): MorphTo
    {
        return $this->morphTo();
    }

    public function responses(): HasMany
    {
        return $this->hasMany(TransactionResponse::class, 'order_id', 'order_id');
    }

    public function latestResponse()
    {
        return $this->hasOne(TransactionResponse::class, 'order_id', 'order_id')->latestOfMany();
    }

    public function isPaid(): bool
    {
        return in_array($this->status, ['capture', 'settlement']);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFailed(): bool
    {
        return in_array($this->status, ['deny', 'cancel', 'expire', 'failure']);
    }

    public function isRefunded(): bool
    {
        return in_array($this->status, ['refund', 'partial_refund']);
    }
}
