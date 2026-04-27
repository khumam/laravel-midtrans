<?php

namespace Khumam\Midtrans\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Khumam\Midtrans\Models\Transaction;

class TransactionResponse extends Model
{
    protected $table = 'midtrans_transaction_responses';

    protected $guarded = [];

    protected $casts = [
        'gross_amount' => 'decimal:2',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'order_id', 'order_id');
    }
}
