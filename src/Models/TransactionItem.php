<?php

namespace Khumam\Midtrans\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionItem extends Model
{
    protected $table = 'midtrans_transaction_items';

    protected $guarded = [];

    protected $casts = [
        'price' => 'integer',
        'quantity' => 'integer',
        'tenor' => 'integer',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'order_id', 'order_id');
    }
}
