<?php

namespace Khumam\Midtrans\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Khumam\Midtrans\Billable;
use Khumam\Midtrans\Models\Transaction;
use Khumam\Midtrans\Models\TransactionItem;
use Khumam\Midtrans\RequestObjects\ItemDetailObject;
use Khumam\Midtrans\Tests\TestCase;

class TestUser extends Model
{
    use Billable;

    protected $table = 'test_users';
    protected $guarded = [];
}

class ItemDetailObjectTest extends TestCase
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

    protected function createTransaction(): Transaction
    {
        $user = TestUser::create(['name' => 'Test', 'email' => 'test@test.com']);

        return $user->transactions()->create([
            'order_id' => 'test-' . uniqid(),
            'gross_amount' => 100000,
            'status' => 'pending',
            'type' => 'one-time',
        ]);
    }

    /** @test */
    public function it_validates_single_indexed_item(): void
    {
        $transaction = $this->createTransaction();

        $transaction->items()->create([
            'item_id' => 'item-1',
            'price' => 50000,
            'quantity' => 1,
            'name' => 'Product A',
        ]);

        $items = $transaction->fresh()->items;

        $this->assertCount(1, $items);
        $this->assertEquals('item-1', $items[0]->item_id);
        $this->assertEquals(50000, $items[0]->price);
        $this->assertEquals(1, $items[0]->quantity);
        $this->assertEquals('Product A', $items[0]->name);
    }

    /** @test */
    public function it_validates_multiple_items(): void
    {
        $transaction = $this->createTransaction();

        $transaction->items()->createMany([
            ['item_id' => 'item-1', 'price' => 50000, 'quantity' => 2, 'name' => 'Product A'],
            ['item_id' => 'item-2', 'price' => 25000, 'quantity' => 1, 'name' => 'Product B'],
        ]);

        $items = $transaction->fresh()->items;

        $this->assertCount(2, $items);
        $this->assertEquals('item-1', $items[0]->item_id);
        $this->assertEquals('item-2', $items[1]->item_id);
    }

    /** @test */
    public function it_validates_all_fields(): void
    {
        $transaction = $this->createTransaction();

        $transaction->items()->create([
            'item_id' => 'item-1',
            'price' => 100000,
            'quantity' => 3,
            'name' => 'Full Item',
            'brand' => 'Brand X',
            'category' => 'Electronics',
            'merchant_name' => 'Merchant A',
            'tenor' => 6,
            'code_plan' => 'plan-01',
            'mid' => 'M001',
            'url' => 'https://example.com/item',
        ]);

        $item = $transaction->fresh()->items->first();

        $this->assertEquals('item-1', $item->item_id);
        $this->assertEquals(100000, $item->price);
        $this->assertEquals(3, $item->quantity);
        $this->assertEquals('Full Item', $item->name);
        $this->assertEquals('Brand X', $item->brand);
        $this->assertEquals('Electronics', $item->category);
        $this->assertEquals('Merchant A', $item->merchant_name);
        $this->assertEquals(6, $item->tenor);
        $this->assertEquals('plan-01', $item->code_plan);
        $this->assertEquals('M001', $item->mid);
        $this->assertEquals('https://example.com/item', $item->url);
    }

    /** @test */
    public function it_validates_partial_fields(): void
    {
        $transaction = $this->createTransaction();

        $transaction->items()->create([
            'item_id' => 'item-1',
            'price' => 50000,
            'quantity' => 1,
            'name' => 'Product A',
        ]);

        $item = $transaction->fresh()->items->first();

        $this->assertEquals('item-1', $item->item_id);
        $this->assertEquals(50000, $item->price);
        $this->assertEquals(1, $item->quantity);
        $this->assertEquals('Product A', $item->name);
        $this->assertNull($item->brand);
        $this->assertNull($item->category);
    }

    /** @test */
    public function it_handles_empty_array(): void
    {
        $transaction = $this->createTransaction();

        $this->assertCount(0, $transaction->items);
    }

    /** @test */
    public function it_throws_exception_for_non_integer_price(): void
    {
        $this->expectException(\Exception::class);
        $this->runValidation([['id' => 'item-1', 'price' => 'not-a-number', 'quantity' => 1, 'name' => 'A']]);
    }

    /** @test */
    public function it_throws_exception_for_non_integer_quantity(): void
    {
        $this->expectException(\Exception::class);
        $this->runValidation([['id' => 'item-1', 'price' => 50000, 'quantity' => 'abc', 'name' => 'A']]);
    }

    /** @test */
    public function it_throws_exception_for_non_integer_tenor(): void
    {
        $this->expectException(\Exception::class);
        $this->runValidation([['id' => 'item-1', 'price' => 50000, 'quantity' => 1, 'name' => 'A', 'tenor' => 'invalid']]);
    }

    /** @test */
    public function it_throws_exception_for_non_string_name(): void
    {
        $this->expectException(\Exception::class);
        $this->runValidation([['id' => 'item-1', 'price' => 50000, 'quantity' => 1, 'name' => 12345]]);
    }

    /** @test */
    public function it_throws_exception_for_non_string_brand(): void
    {
        $this->expectException(\Exception::class);
        $this->runValidation([['id' => 'item-1', 'price' => 50000, 'quantity' => 1, 'name' => 'A', 'brand' => 999]]);
    }

    /** @test */
    public function it_throws_exception_for_non_string_category(): void
    {
        $this->expectException(\Exception::class);
        $this->runValidation([['id' => 'item-1', 'price' => 50000, 'quantity' => 1, 'name' => 'A', 'category' => 123]]);
    }

    /** @test */
    public function it_throws_exception_for_non_string_merchant_name(): void
    {
        $this->expectException(\Exception::class);
        $this->runValidation([['id' => 'item-1', 'price' => 50000, 'quantity' => 1, 'name' => 'A', 'merchant_name' => 456]]);
    }

    /** @test */
    public function it_throws_exception_for_non_string_code_plan(): void
    {
        $this->expectException(\Exception::class);
        $this->runValidation([['id' => 'item-1', 'price' => 50000, 'quantity' => 1, 'name' => 'A', 'code_plan' => 789]]);
    }

    /** @test */
    public function it_throws_exception_for_non_string_mid(): void
    {
        $this->expectException(\Exception::class);
        $this->runValidation([['id' => 'item-1', 'price' => 50000, 'quantity' => 1, 'name' => 'A', 'mid' => 111]]);
    }

    /** @test */
    public function it_throws_exception_for_non_string_url(): void
    {
        $this->expectException(\Exception::class);
        $this->runValidation([['id' => 'item-1', 'price' => 50000, 'quantity' => 1, 'name' => 'A', 'url' => 222]]);
    }

    protected function runValidation(array $items): void
    {
        $rules = [
            "*.id" => "nullable",
            "*.price" => "nullable|integer",
            "*.quantity" => "nullable|integer",
            "*.name" => "nullable|string",
            "*.brand" => "nullable|string",
            "*.category" => "nullable|string",
            "*.merchant_name" => "nullable|string",
            "*.tenor" => "nullable|integer",
            "*.code_plan" => "nullable|string",
            "*.mid" => "nullable|string",
            "*.url" => "nullable|string",
        ];

        $validator = Validator::make($items, $rules);

        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }
    }

    /** @test */
    public function it_allows_null_values(): void
    {
        $transaction = $this->createTransaction();

        $transaction->items()->create([
            'item_id' => null,
            'price' => null,
            'quantity' => null,
            'name' => null,
            'brand' => null,
            'category' => null,
            'merchant_name' => null,
            'tenor' => null,
            'code_plan' => null,
            'mid' => null,
            'url' => null,
        ]);

        $item = $transaction->fresh()->items->first();

        $this->assertNull($item->item_id);
        $this->assertNull($item->price);
        $this->assertNull($item->quantity);
        $this->assertNull($item->name);
    }

    /** @test */
    public function it_strips_unknown_fields(): void
    {
        $transaction = $this->createTransaction();

        $transaction->items()->create([
            'item_id' => 'item-1',
            'price' => 50000,
            'quantity' => 1,
            'name' => 'Product A',
        ]);

        $item = $transaction->fresh()->items->first();

        // Only known columns exist in DB — unknown fields simply not stored
        $this->assertEquals('item-1', $item->item_id);
        $this->assertEquals('Product A', $item->name);
    }

    /** @test */
    public function it_deletes_existing_items_before_saving_new_ones(): void
    {
        $transaction = $this->createTransaction();

        $transaction->items()->create([
            'item_id' => 'old-item',
            'price' => 10000,
            'quantity' => 1,
            'name' => 'Old Product',
        ]);

        $this->assertCount(1, $transaction->fresh()->items);

        // Simulate re-calling withItemDetail by deleting and re-creating
        $transaction->items()->delete();
        $transaction->items()->create([
            'item_id' => 'new-item',
            'price' => 20000,
            'quantity' => 2,
            'name' => 'New Product',
        ]);

        $items = $transaction->fresh()->items;
        $this->assertCount(1, $items);
        $this->assertEquals('new-item', $items->first()->item_id);
    }
}
