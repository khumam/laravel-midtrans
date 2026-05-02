<?php

namespace Khumam\Midtrans\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Khumam\Midtrans\Billable;
use Khumam\Midtrans\Checkout;
use Khumam\Midtrans\Models\Transaction;
use Khumam\Midtrans\Tests\TestCase;

class CheckoutRedirectUser extends Model
{
    use Billable;

    protected $table = 'test_checkout_users';
    protected $guarded = [];
}

class CheckoutRedirectTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('midtrans.server_key', 'SB-Mid-server-test');
        $app['config']->set('midtrans.is_sandbox', true);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->app['db']->connection()->getSchemaBuilder()->create('test_checkout_users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->timestamps();
        });

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    protected function createCheckout(Transaction $transaction, string $body): Checkout
    {
        $mock = new MockHandler([
            new Response(200, [], $body),
        ]);

        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $checkout = new Checkout($transaction);

        // Swap the Guzzle client with our mock
        $reflection = new \ReflectionProperty($checkout, 'client');
        $reflection->setAccessible(true);
        $reflection->setValue($checkout, $client);

        return $checkout;
    }

    protected function createUser(): CheckoutRedirectUser
    {
        return CheckoutRedirectUser::create(['name' => 'Test', 'email' => 'test@test.com']);
    }

    protected function midtransResponse(): string
    {
        return json_encode([
            'token' => 'snap-token-123',
            'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/pay?token=snap-token-123',
        ]);
    }

    /** @test */
    public function getRedirectUrl_returns_snap_redirect_url(): void
    {
        $user = $this->createUser();
        $transaction = $user->transactions()->create([
            'order_id' => 'test-url-1',
            'gross_amount' => 50000,
            'status' => 'pending',
            'type' => 'one-time',
        ]);

        $checkout = $this->createCheckout($transaction, $this->midtransResponse());
        $url = $checkout->getRedirectUrl();

        $this->assertEquals('https://app.sandbox.midtrans.com/snap/v2/pay?token=snap-token-123', $url);
    }

    /** @test */
    public function getRedirectUrl_stores_snap_token_and_url_on_transaction(): void
    {
        $user = $this->createUser();
        $transaction = $user->transactions()->create([
            'order_id' => 'test-url-2',
            'gross_amount' => 50000,
            'status' => 'pending',
            'type' => 'one-time',
        ]);

        $checkout = $this->createCheckout($transaction, $this->midtransResponse());
        $checkout->getRedirectUrl();

        $fresh = $transaction->fresh();

        $this->assertEquals('snap-token-123', $fresh->snap_token);
        $this->assertEquals('https://app.sandbox.midtrans.com/snap/v2/pay?token=snap-token-123', $fresh->snap_redirect_url);
    }

    /** @test */
    public function redirectTo_returns_redirect_response(): void
    {
        $user = $this->createUser();
        $transaction = $user->transactions()->create([
            'order_id' => 'test-redirect-1',
            'gross_amount' => 75000,
            'status' => 'pending',
            'type' => 'one-time',
        ]);

        $checkout = $this->createCheckout($transaction, $this->midtransResponse());
        $response = $checkout->redirectTo('home');

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertEquals('https://app.sandbox.midtrans.com/snap/v2/pay?token=snap-token-123', $response->getTargetUrl());
    }

    /** @test */
    public function redirectTo_stores_snap_token_and_url_on_transaction(): void
    {
        $user = $this->createUser();
        $transaction = $user->transactions()->create([
            'order_id' => 'test-redirect-2',
            'gross_amount' => 75000,
            'status' => 'pending',
            'type' => 'one-time',
        ]);

        $checkout = $this->createCheckout($transaction, $this->midtransResponse());
        $checkout->redirectTo('home');

        $fresh = $transaction->fresh();

        $this->assertEquals('snap-token-123', $fresh->snap_token);
        $this->assertEquals('https://app.sandbox.midtrans.com/snap/v2/pay?token=snap-token-123', $fresh->snap_redirect_url);
    }

    /** @test */
    public function getRedirectUrl_with_item_details(): void
    {
        $user = $this->createUser();
        $transaction = $user->transactions()->create([
            'order_id' => 'test-url-items-1',
            'gross_amount' => 100000,
            'status' => 'pending',
            'type' => 'one-time',
        ]);

        $checkout = $this->createCheckout($transaction, $this->midtransResponse());
        $checkout->withItemDetail([
            ['id' => 'item1', 'price' => 50000, 'quantity' => 2, 'name' => 'Product A'],
        ]);

        $url = $checkout->getRedirectUrl();

        $this->assertEquals('https://app.sandbox.midtrans.com/snap/v2/pay?token=snap-token-123', $url);
        $this->assertCount(1, $transaction->fresh()->items);
    }

    /** @test */
    public function getRedirectUrl_with_tax(): void
    {
        $user = $this->createUser();
        $transaction = $user->transactions()->create([
            'order_id' => 'test-url-tax-1',
            'gross_amount' => 100000,
            'status' => 'pending',
            'type' => 'one-time',
        ]);

        $checkout = $this->createCheckout($transaction, $this->midtransResponse());
        $checkout->withTax(5000);

        $url = $checkout->getRedirectUrl();

        $this->assertEquals('https://app.sandbox.midtrans.com/snap/v2/pay?token=snap-token-123', $url);
        $this->assertEquals(105000, (float) $transaction->fresh()->gross_amount);
        $this->assertEquals(5000, (float) $transaction->fresh()->tax_amount);
    }

    /** @test */
    public function getRedirectUrl_with_tax_percentage(): void
    {
        $user = $this->createUser();
        $transaction = $user->transactions()->create([
            'order_id' => 'test-url-taxpct-1',
            'gross_amount' => 100000,
            'status' => 'pending',
            'type' => 'one-time',
        ]);

        $checkout = $this->createCheckout($transaction, $this->midtransResponse());
        $checkout->withTaxPercentage(10);

        $url = $checkout->getRedirectUrl();

        $this->assertEquals('https://app.sandbox.midtrans.com/snap/v2/pay?token=snap-token-123', $url);
        $this->assertEquals(110000, (float) $transaction->fresh()->gross_amount);
        $this->assertEquals(10000, (float) $transaction->fresh()->tax_amount);
        $this->assertEquals(10, (float) $transaction->fresh()->tax_percentage);
    }

    /** @test */
    public function getRedirectUrl_with_subscription(): void
    {
        $user = $this->createUser();
        $transaction = $user->transactions()->create([
            'order_id' => 'test-url-sub-1',
            'gross_amount' => 200000,
            'status' => 'pending',
            'type' => 'subscription',
            'ends_at' => now()->addMonth(),
        ]);

        $checkout = $this->createCheckout($transaction, $this->midtransResponse());
        $url = $checkout->getRedirectUrl();

        $this->assertEquals('https://app.sandbox.midtrans.com/snap/v2/pay?token=snap-token-123', $url);
        $this->assertEquals('subscription', $transaction->fresh()->type);
    }

    /** @test */
    public function getRedirectUrl_creates_customer_record(): void
    {
        $user = $this->createUser();
        $transaction = $user->transactions()->create([
            'order_id' => 'test-url-cust-1',
            'gross_amount' => 50000,
            'status' => 'pending',
            'type' => 'one-time',
        ]);

        $checkout = $this->createCheckout($transaction, $this->midtransResponse());
        $checkout->getRedirectUrl();

        $customer = $user->fresh()->midtransCustomer;

        $this->assertNotNull($customer);
        $this->assertEquals('Test', $customer->first_name);
        $this->assertEquals('test@test.com', $customer->email);
    }
}
