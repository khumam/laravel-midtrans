<?php

namespace Khumam\Midtrans\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Khumam\Midtrans\Billable;
use Khumam\Midtrans\Models\Customer;
use Khumam\Midtrans\Tests\TestCase;

class CustomerUser extends Model
{
    use Billable;

    protected $table = 'test_customer_users';
    protected $guarded = [];
}

class CustomerTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('midtrans.server_key', 'SB-Mid-server-test');
        $app['config']->set('midtrans.is_sandbox', true);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->app['db']->connection()->getSchemaBuilder()->create('test_customer_users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->timestamps();
        });

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    protected function createUser(): CustomerUser
    {
        return CustomerUser::create(['name' => 'John Doe', 'email' => 'john@example.com']);
    }

    /** @test */
    public function it_creates_customer_on_checkout(): void
    {
        $user = $this->createUser();

        $user->checkout(50000);

        $customer = $user->fresh()->midtransCustomer;

        $this->assertNotNull($customer);
        $this->assertEquals('John Doe', $customer->first_name);
        $this->assertEquals('john@example.com', $customer->email);
    }

    /** @test */
    public function it_upserts_customer_on_subsequent_checkout(): void
    {
        $user = $this->createUser();

        $user->checkout(50000);
        $this->assertEquals('John Doe', $user->fresh()->midtransCustomer->first_name);

        $user->checkout(75000);
        $this->assertCount(1, Customer::where('billable_id', $user->id)->get());
    }

    /** @test */
    public function it_stores_all_customer_fields(): void
    {
        $user = $this->createUser();

        // Create a transaction so the Checkout can be built
        $transaction = $user->transactions()->create([
            'order_id' => 'test-order-cust-1',
            'gross_amount' => 100000,
            'status' => 'pending',
            'type' => 'one-time',
        ]);

        // Manually upsert via the model
        Customer::updateOrCreate(
            ['billable_type' => get_class($user), 'billable_id' => $user->id],
            [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'email' => 'jane@example.com',
                'phone' => '08123456789',
                'billing_first_name' => 'Jane',
                'billing_last_name' => 'Smith',
                'billing_phone' => '08123456789',
                'billing_address' => 'Jl. Sudirman No. 1',
                'billing_city' => 'Jakarta',
                'billing_postal_code' => '12190',
                'billing_country_code' => 'IDN',
                'shipping_first_name' => 'John',
                'shipping_last_name' => 'Doe',
                'shipping_phone' => '08987654321',
                'shipping_address' => 'Jl. Thamrin No. 2',
                'shipping_city' => 'Bandung',
                'shipping_postal_code' => '40115',
                'shipping_country_code' => 'IDN',
            ],
        );

        $customer = Customer::first();

        $this->assertEquals('Jane', $customer->first_name);
        $this->assertEquals('Smith', $customer->last_name);
        $this->assertEquals('jane@example.com', $customer->email);
        $this->assertEquals('08123456789', $customer->phone);
        $this->assertEquals('Jane', $customer->billing_first_name);
        $this->assertEquals('Jl. Sudirman No. 1', $customer->billing_address);
        $this->assertEquals('Jakarta', $customer->billing_city);
        $this->assertEquals('12190', $customer->billing_postal_code);
        $this->assertEquals('IDN', $customer->billing_country_code);
        $this->assertEquals('John', $customer->shipping_first_name);
        $this->assertEquals('Jl. Thamrin No. 2', $customer->shipping_address);
        $this->assertEquals('Bandung', $customer->shipping_city);
        $this->assertEquals('40115', $customer->shipping_postal_code);
        $this->assertEquals('IDN', $customer->shipping_country_code);
    }

    /** @test */
    public function customer_belongs_to_billable(): void
    {
        $user = $this->createUser();

        Customer::create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'first_name' => 'John',
            'email' => 'john@example.com',
        ]);

        $customer = Customer::first();

        $this->assertEquals($user->id, $customer->billable->id);
        $this->assertInstanceOf(get_class($user), $customer->billable);
    }

    /** @test */
    public function billable_has_midtrans_customer_relation(): void
    {
        $user = $this->createUser();

        $this->assertNull($user->midtransCustomer);

        Customer::create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'first_name' => 'John',
            'email' => 'john@example.com',
        ]);

        $this->assertNotNull($user->fresh()->midtransCustomer);
    }

    /** @test */
    public function different_users_have_different_customers(): void
    {
        $user1 = CustomerUser::create(['name' => 'User One', 'email' => 'one@test.com']);
        $user2 = CustomerUser::create(['name' => 'User Two', 'email' => 'two@test.com']);

        Customer::create(['billable_type' => get_class($user1), 'billable_id' => $user1->id, 'first_name' => 'User One']);
        Customer::create(['billable_type' => get_class($user2), 'billable_id' => $user2->id, 'first_name' => 'User Two']);

        $this->assertEquals('User One', $user1->fresh()->midtransCustomer->first_name);
        $this->assertEquals('User Two', $user2->fresh()->midtransCustomer->first_name);
    }

    /** @test */
    public function it_updates_existing_customer_data(): void
    {
        $user = $this->createUser();

        Customer::create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'first_name' => 'Old Name',
            'email' => 'old@example.com',
            'phone' => '111',
        ]);

        Customer::updateOrCreate(
            ['billable_type' => get_class($user), 'billable_id' => $user->id],
            ['first_name' => 'New Name', 'email' => 'new@example.com', 'phone' => '222'],
        );

        $customer = $user->fresh()->midtransCustomer;

        $this->assertEquals('New Name', $customer->first_name);
        $this->assertEquals('new@example.com', $customer->email);
        $this->assertEquals('222', $customer->phone);
        $this->assertCount(1, Customer::all());
    }
}
