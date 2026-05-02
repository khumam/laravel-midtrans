# Laravel Midtrans

Laravel package for Midtrans payment gateway integration. Supports Laravel 11, 12, and 13.

Inspired by [Laravel Cashier (Paddle)](https://github.com/laravel/cashier-paddle). Simplified flow: checkout and webhook handling.

## Installation

```bash
composer require khumam/laravel-midtrans
```

Publish config and migrations:

```bash
php artisan vendor:publish --tag=midtrans-config
php artisan vendor:publish --tag=midtrans-migrations
php artisan migrate
```

Add to `.env`:

```env
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxx
MIDTRANS_IS_SANDBOX=true
```

## Setup

Add the `Billable` trait to your `User` model (or any billable model):

```php
use Khumam\Midtrans\Billable;

class User extends Model
{
    use Billable;
}
```

## Usage

### One-Time Payment

```php
use Khumam\Midtrans\Billable;

// In your controller
public function pay(Request $request)
{
    return $request->user()
        ->checkout(50000)
        ->withItemDetail([
            ['id' => 'item1', 'price' => 50000, 'quantity' => 1, 'name' => 'Product A'],
        ])
        ->secureCreditCard()
        ->redirectTo('payment.success');
}
```

The `checkout()` method:
1. Cancels any existing pending transactions for the user
2. Creates a new transaction record
3. Calls the Midtrans Snap API
4. Redirects user to the Midtrans payment page

### Subscription

```php
use Khumam\Midtrans\Enums\MidtransPeriod;

// Monthly subscription
return $user->subscribe(100000, MidtransPeriod::Monthly)
    ->withSubscriptionSchedule([
        'interval' => 1,
        'interval_unit' => 'month',
    ])
    ->redirectTo('subscription.success');

// Annual subscription
return $user->subscribe(1000000, MidtransPeriod::Annually)
    ->redirectTo('subscription.success');
```

Available periods:

| Period | Enum Value |
|--------|-----------|
| Monthly | `MidtransPeriod::Monthly` |
| Quarterly | `MidtransPeriod::Quarterly` |
| Semi-Annually | `MidtransPeriod::SemiAnnually` |
| Annually | `MidtransPeriod::Annually` |

### Check Subscription Status

```php
// Check if user has active subscription
if ($user->subscribed()) {
    // User is subscribed
}

// Get the active subscription transaction
$subscription = $user->subscription();
// Returns Transaction model or null
```

## Checkout Builder Methods

All methods below are chainable on the `Checkout` object returned by `checkout()` or `subscribe()`.

### Payment Options

```php
->secureCreditCard()                    // Enable 3DS/secure credit card
->withCreditCard([                      // Custom credit card config
    'token_id' => 'xxx',
    'bank' => 'bni',
    'installment_term' => 3,
])
->withBankTransfer([                    // Bank transfer (VA)
    'bank' => 'bca',
    'va_number' => '1234567890',
])
->withGopay([                           // GoPay config
    'enable_callback' => true,
    'callback_url' => 'https://example.com/callback',
])
->withQris([                            // QRIS config
    'acquirer' => 'gopay',
])
```

### Transaction Details

```php
->withItemDetail([
    ['id' => 'item1', 'price' => 50000, 'quantity' => 1, 'name' => 'Product A'],
    ['id' => 'item2', 'price' => 25000, 'quantity' => 2, 'name' => 'Product B'],
])
->withCustomerDetail([                  // Override customer details
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john@example.com',
    'phone' => '08123456789',
    'billing_address' => [
        'first_name' => 'John',
        'address' => 'Jl. Sudirman No. 1',
        'city' => 'Jakarta',
        'postal_code' => '12190',
        'country_code' => 'IDN',
    ],
])
->withCustomExpiry([                    // Custom expiration
    'expiry_duration' => '60',
    'unit' => 'minute',
])
```

Default customer details are auto-populated from the billable model's `name` and `email` fields. Override with custom field names:

```php
$user->checkout(50000, [
    'name_field' => 'full_name',
    'email_field' => 'email_address',
]);
```

### Subscription Schedule

```php
->withSubscriptionSchedule([
    'interval' => 1,
    'interval_unit' => 'month',
    'max_interval' => 12,
])
```

### Tax

```php
->withTax(5000)                         // Fixed tax: adds 5000 to gross_amount
->withTaxPercentage(11)                 // Percentage tax: 11% of gross_amount
```

- `withTax(float $taxPrice)` — adds fixed tax amount to `gross_amount`, stores `tax_amount`, adds a "Tax" item detail
- `withTaxPercentage(float $percent)` — calculates `gross_amount * percent / 100`, adds to `gross_amount`, stores `tax_amount` and `tax_percentage`, adds a "Tax" item detail

### Finalize

```php
->redirectTo('route.name')              // Redirects user to Midtrans Snap payment page
->getRedirectUrl()                      // Returns the Snap redirect URL as string (no redirect)
```

Use `getRedirectUrl()` when you need the URL without auto-redirecting (SPA, API responses, etc.):

```php
// Return URL as JSON for frontend
$url = $user->checkout(50000)->getRedirectUrl();
return response()->json(['payment_url' => $url]);

// Or redirect manually
return redirect($user->checkout(50000)->getRedirectUrl());
```

## Webhook

The package automatically registers a webhook endpoint at `POST /midtrans/webhook`.

Configure this URL in your Midtrans Dashboard under **Settings > Payment Notification URL**:

```
https://yourdomain.com/midtrans/webhook
```

### Webhook Flow

1. Receives POST from Midtrans
2. Validates signature: `SHA512(order_id + status_code + gross_amount + server_key)`
3. Updates transaction status
4. Stores full response in `midtrans_transaction_responses` table

### Transaction Statuses

| Status | Meaning |
|--------|---------|
| `capture` | Card payment captured successfully. Funds received. |
| `settlement` | Transaction settled. Funds credited to account. |
| `pending` | Awaiting customer payment. |
| `deny` | Payment rejected by provider or fraud system. |
| `cancel` | Transaction cancelled. |
| `expire` | Payment window expired. |
| `failure` | Unexpected error during processing. |
| `refund` | Full refund issued. |
| `partial_refund` | Partial refund issued. |
| `authorize` | Card pre-authorized (advanced feature). |

## Transaction Model

```php
use Khumam\Midtrans\Models\Transaction;

$transaction = Transaction::where('order_id', $orderId)->first();

$transaction->isPaid();          // true if capture or settlement
$transaction->isPending();       // true if pending
$transaction->isFailed();        // true if deny/cancel/expire/failure
$transaction->isRefunded();      // true if refund or partial_refund
$transaction->onGracePeriod(3);  // true if ends_at passed < 3 days ago

$transaction->billable;          // The User model (morph relationship)
$transaction->responses;         // All webhook responses (HasMany)
$transaction->latestResponse;    // Latest webhook response (HasOne)
$transaction->items;             // Transaction items (HasMany TransactionItem)
$transaction->tax_amount;        // Tax amount (decimal)
$transaction->tax_percentage;    // Tax percentage (decimal)
```

### Transaction Items

When you use `withItemDetail()`, items are persisted and linked to the transaction. Items cascade delete with the parent transaction.

```php
use Khumam\Midtrans\Models\TransactionItem;

$transaction->items;                    // Collection of TransactionItem
$transaction->items->first()->name;     // 'Product A'

$item = TransactionItem::find(1);
$item->transaction;                     // BelongsTo Transaction
```

> **Note:** Must be an indexed array of items (`[[...], [...]]`), not a single associative array.

### Customers

Customer details are automatically upserted when `withCustomerDetail()` is called (happens by default on every checkout). One customer record per billable model — subsequent checkouts update the existing record.

```php
use Khumam\Midtrans\Models\Customer;

$user->midtransCustomer;                // Customer model or null

$customer = Customer::find(1);
$customer->billable;                    // The User model (morph relationship)
```

## Testing

```bash
composer test
```

Or directly:

```bash
vendor/bin/phpunit
```

### Writing Tests

This package uses [Orchestra Testbench](https://packages.tools/testbench.html) for Laravel package testing. Extend the base `TestCase`:

```php
namespace Khumam\Midtrans\Tests\Feature;

use Khumam\Midtrans\Tests\TestCase;

class MyTest extends TestCase
{
    // database migrations run automatically
    // config: midtrans.server_key and midtrans.is_sandbox are pre-set
}
```

### Test Structure

```
tests/
├── TestCase.php                   # Base test (Orchestra Testbench)
├── Unit/
│   ├── ItemDetailObjectTest       # Item detail validation, types, edge cases
│   └── TaxObjectTest              # Tax calculations, fixed & percentage
└── Feature/
    ├── TransactionItemTest        # Item relations, DB, cascade delete
    ├── CustomerTest               # Customer upsert, relations, billing/shipping
    └── CheckoutRedirectTest       # getRedirectUrl, redirectTo, mocked API
```

## License

MIT
