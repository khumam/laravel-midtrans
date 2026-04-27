<?php

namespace Khumam\Midtrans;

use GuzzleHttp\Client;
use Khumam\Midtrans\Models\Transaction;
use Khumam\Midtrans\RequestObjects\BankTransferObject;
use Khumam\Midtrans\RequestObjects\CreditCardObject;
use Khumam\Midtrans\RequestObjects\CustomerDetailObject;
use Khumam\Midtrans\RequestObjects\CustomExpiryObject;
use Khumam\Midtrans\RequestObjects\EChannelObject;
use Khumam\Midtrans\RequestObjects\GopayObject;
use Khumam\Midtrans\RequestObjects\ItemDetailObject;
use Khumam\Midtrans\RequestObjects\OverTheCounterObject;
use Khumam\Midtrans\RequestObjects\OvoObject;
use Khumam\Midtrans\RequestObjects\PaymentAmountObject;
use Khumam\Midtrans\RequestObjects\QrisObject;
use Khumam\Midtrans\RequestObjects\SellerDetailObject;
use Khumam\Midtrans\RequestObjects\ShopeePayObject;
use Khumam\Midtrans\RequestObjects\SubscriptionScheduleObject;
use Khumam\Midtrans\RequestObjects\TransactionDetailObject;
use Khumam\Midtrans\RequestObjects\VaNumberObject;

class Checkout
{
    use BankTransferObject, CreditCardObject, CustomerDetailObject, CustomExpiryObject, EChannelObject, GopayObject;
    use SellerDetailObject, ShopeePayObject, SubscriptionScheduleObject, TransactionDetailObject, VaNumberObject;
    use ItemDetailObject, OverTheCounterObject, OvoObject, PaymentAmountObject, QrisObject;

    protected Client $client;
    protected Transaction $transaction;
    protected array $payload = [];
    protected string $redirectRoute;
    protected ?string $nameField = 'name';
    protected ?string $emailField = 'email';

    public function __construct(Transaction $transaction, array $options = [])
    {
        $this->transaction = $transaction;

        $baseUrl = config('midtrans.is_sandbox')
            ? 'https://app.sandbox.midtrans.com'
            : 'https://app.midtrans.com';

        $serverKey = config('midtrans.server_key');

        $this->client = new Client([
            'base_uri' => $baseUrl,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode("$serverKey:"),
            ],
        ]);

        $this->withTransactionDetail([
            'order_id' => $transaction->order_id,
            'gross_amount' => (float) $transaction->gross_amount,
        ]);

        if (isset($options['name_field'])) {
            $this->nameField = $options['name_field'];
        }
        if (isset($options['email_field'])) {
            $this->emailField = $options['email_field'];
        }

        $this->buildDefaultCustomerDetails();
    }

    protected function buildDefaultCustomerDetails(): void
    {
        $billable = $this->transaction->billable;

        if ($billable) {
            $this->withCustomerDetail([
                'first_name' => $billable->{$this->nameField} ?? null,
                'last_name' => null,
                'email' => $billable->{$this->emailField} ?? null,
            ]);
        }
    }

    public function secureCreditCard(): self
    {
        $this->payload['credit_card'] = ['secure' => true];

        return $this;
    }
    
    public function redirectTo(string $route): mixed
    {
        $this->redirectRoute = $route;

        return $this->send();
    }

    protected function send(): mixed
    {
        $response = $this->client->post('/snap/v1/transactions', [
            'json' => $this->payload,
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        $this->transaction->update([
            'snap_token' => $data['token'],
            'snap_redirect_url' => $data['redirect_url'],
        ]);

        return redirect($data['redirect_url']);
    }
}
