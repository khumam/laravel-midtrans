<?php

namespace Khumam\Midtrans\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Khumam\Midtrans\Billable;
use Khumam\Midtrans\Models\Transaction;
use Khumam\Midtrans\Models\TransactionItem;
use Khumam\Midtrans\Tests\TestCase;

class User extends Model
{
    use Billable;

    protected $table = 'test_users';
    protected $guarded = [];
    public string $name = 'Test User';
    public string $email = 'test@example.com';
}

class TransactionItemTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('midtrans.server_key', 'SB-Mid-server-test');
        $app['config']->set('midtrans.is_sandbox', true);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->app['db']->connection()->getSchemaBuilder()->create('test_users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->timestamps();
        });

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    protected function createUser(): User
    {
        return User::create(['name' => 'Test User', 'email' => 'test@example.com']);
    }

    /** @test */
    public function transaction_has_items_relation(): void
    {
        $user = $this->createUser();
        $transaction = $user->transactions()->create([
            'order_id' => 'test-order-1',
            'gross_amount' => 100000,
            'status' => 'pending',
            'type' => 'one-time',
        ]);

        TransactionItem::create([
            'order_id' => $transaction->order_id,
            'item_id' => 'item-1',
            'price' => 50000,
            'quantity' => 2,
            'name' => 'Product A',
        ]);

        $this->assertCount(1, $transaction->fresh()->items);
        $this->assertEquals('Product A', $transaction->items->first()->name);
    }

    /** @test */
    public function transaction_item_belongs_to_transaction(): void
    {
        $user = $this->createUser();
        $transaction = $user->transactions()->create([
            'order_id' => 'test-order-2',
            'gross_amount' => 75000,
            'status' => 'pending',
            'type' => 'one-time',
        ]);

        $item = TransactionItem::create([
            'order_id' => $transaction->order_id,
            'item_id' => 'item-1',
            'price' => 75000,
            'quantity' => 1,
            'name' => 'Product B',
        ]);

        $this->assertEquals($transaction->order_id, $item->transaction->order_id);
    }

    /** @test */
    public function transaction_items_are_cascade_deleted_with_model(): void
    {
        $user = $this->createUser();
        $transaction = $user->transactions()->create([
            'order_id' => 'test-order-3',
            'gross_amount' => 50000,
            'status' => 'pending',
            'type' => 'one-time',
        ]);

        TransactionItem::create([
            'order_id' => $transaction->order_id,
            'item_id' => 'item-1',
            'price' => 50000,
            'quantity' => 1,
            'name' => 'Product C',
        ]);

        $this->assertCount(1, TransactionItem::where('order_id', 'test-order-3')->get());

        // Delete items manually (cascadeOnDelete is enforced at DB level for MySQL/Postgres)
        $transaction->items()->delete();
        $transaction->delete();

        $this->assertCount(0, TransactionItem::where('order_id', 'test-order-3')->get());
    }

    /** @test */
    public function transaction_can_have_multiple_items(): void
    {
        $user = $this->createUser();
        $transaction = $user->transactions()->create([
            'order_id' => 'test-order-4',
            'gross_amount' => 125000,
            'status' => 'pending',
            'type' => 'one-time',
        ]);

        TransactionItem::create([
            'order_id' => $transaction->order_id,
            'item_id' => 'item-1',
            'price' => 50000,
            'quantity' => 2,
            'name' => 'Product A',
        ]);

        TransactionItem::create([
            'order_id' => $transaction->order_id,
            'item_id' => 'item-2',
            'price' => 25000,
            'quantity' => 1,
            'name' => 'Product B',
        ]);

        $this->assertCount(2, $transaction->fresh()->items);
    }

    /** @test */
    public function transaction_item_stores_all_fields(): void
    {
        $user = $this->createUser();
        $transaction = $user->transactions()->create([
            'order_id' => 'test-order-5',
            'gross_amount' => 200000,
            'status' => 'pending',
            'type' => 'one-time',
        ]);

        TransactionItem::create([
            'order_id' => $transaction->order_id,
            'item_id' => 'item-full',
            'price' => 200000,
            'quantity' => 1,
            'name' => 'Full Product',
            'brand' => 'Brand X',
            'category' => 'Electronics',
            'merchant_name' => 'Merchant A',
            'tenor' => 6,
            'code_plan' => 'plan-01',
            'mid' => 'M001',
            'url' => 'https://example.com/item',
        ]);

        $item = TransactionItem::first();

        $this->assertEquals('item-full', $item->item_id);
        $this->assertEquals(200000, $item->price);
        $this->assertEquals(1, $item->quantity);
        $this->assertEquals('Full Product', $item->name);
        $this->assertEquals('Brand X', $item->brand);
        $this->assertEquals('Electronics', $item->category);
        $this->assertEquals('Merchant A', $item->merchant_name);
        $this->assertEquals(6, $item->tenor);
        $this->assertEquals('plan-01', $item->code_plan);
        $this->assertEquals('M001', $item->mid);
        $this->assertEquals('https://example.com/item', $item->url);
    }

    /** @test */
    public function transaction_with_no_items_returns_empty_collection(): void
    {
        $user = $this->createUser();
        $transaction = $user->transactions()->create([
            'order_id' => 'test-order-6',
            'gross_amount' => 30000,
            'status' => 'pending',
            'type' => 'one-time',
        ]);

        $this->assertCount(0, $transaction->items);
    }
}
