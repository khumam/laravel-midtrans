<?php

namespace Khumam\Midtrans\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Khumam\Midtrans\Models\Transaction;
use Khumam\Midtrans\Models\TransactionResponse;

class WebhookController extends Controller
{
    public function handleWebhook(Request $request): Response
    {
        $payload = $request->all();

        if (! $this->validateSignature($payload)) {
            return new Response('Invalid signature', 403);
        }

        $transaction = Transaction::where('order_id', $payload['order_id'])->first();

        if (! $transaction) {
            return new Response('Transaction not found', 404);
        }

        $transaction->update([
            'status' => $payload['transaction_status'],
        ]);

        $this->storeResponse($payload);

        return new Response('OK', 200);
    }

    protected function validateSignature(array $payload): bool
    {
        $serverKey = config('midtrans.server_key');

        $expectedSignature = hash('sha512',
            $payload['order_id']
            . $payload['status_code']
            . $payload['gross_amount']
            . $serverKey
        );

        return hash_equals($expectedSignature, $payload['signature_key'] ?? '');
    }

    protected function storeResponse(array $payload): void
    {
        TransactionResponse::create([
            'order_id' => $payload['order_id'],
            'transaction_type' => $payload['transaction_type'] ?? null,
            'transaction_time' => $payload['transaction_time'],
            'transaction_status' => $payload['transaction_status'],
            'transaction_id' => $payload['transaction_id'],
            'status_message' => $payload['status_message'] ?? null,
            'status_code' => $payload['status_code'] ?? null,
            'signature_key' => $payload['signature_key'] ?? null,
            'settlement_time' => $payload['settlement_time'] ?? null,
            'payment_type' => $payload['payment_type'],
            'merchant_id' => $payload['merchant_id'] ?? null,
            'masked_card' => $payload['masked_card'] ?? null,
            'gross_amount' => (float) ($payload['gross_amount'] ?? 0),
            'fraud_status' => $payload['fraud_status'] ?? null,
            'eci' => $payload['eci'] ?? null,
            'currency' => $payload['currency'] ?? 'IDR',
            'channel_response_message' => $payload['channel_response_message'] ?? null,
            'channel_response_code' => $payload['channel_response_code'] ?? null,
            'card_type' => $payload['card_type'] ?? null,
            'bank' => $payload['bank'] ?? null,
            'approval_code' => $payload['approval_code'] ?? null,
            'merchant_cross_reference_id' => $payload['merchant_cross_reference_id'] ?? null,
            'issuer' => $payload['issuer'] ?? null,
            'acquirer' => $payload['acquirer'] ?? null,
        ]);
    }
}
