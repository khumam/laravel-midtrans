<?php

namespace Khumam\Midtrans;

use Illuminate\Support\Str;
use Khumam\Midtrans\Enums\MidtransPeriod;
use Khumam\Midtrans\Models\Transaction;

trait Billable
{
    public function midtransTransactions()
    {
        return $this->morphMany(Transaction::class, 'billable');
    }

    public function checkout(float $grossAmount, array $options = []): Checkout
    {
        $orderId = Str::uuid()->toString();

        $this->midtransTransactions()->where('status', 'pending')->update(['status' => 'cancelled']);
        $transaction = $this->midtransTransactions()->create([
            'order_id' => $orderId,
            'gross_amount' => $grossAmount,
            'status' => 'pending',
            'type' => 'one-time',
        ]);

        return new Checkout($transaction, [
            'name_field' => 'name',
            'email_field' => 'email',
            ...$options,
        ]);
    }

    public function subscribe(float $grossAmount, MidtransPeriod $period, array $options = []): Checkout
    {
        $orderId = Str::uuid()->toString();

        $this->midtransTransactions()->where('status', 'pending')->update(['status' => 'cancelled']);
        $transaction = $this->midtransTransactions()->create([
            'order_id' => $orderId,
            'gross_amount' => $grossAmount,
            'status' => 'pending',
            'type' => 'subscription',
            'ends_at' => $period->toDate(),
        ]);

        return new Checkout($transaction, [
            'name_field' => 'name',
            'email_field' => 'email',
            ...$options,
        ]);
    }
}
