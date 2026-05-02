<?php

namespace Khumam\Midtrans\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Khumam\Midtrans\Billable;
use Khumam\Midtrans\Models\Transaction;
use Khumam\Midtrans\Models\TransactionItem;
use Khumam\Midtrans\Tests\TestCase;

class TaxUser extends Model
{
    use Billable;

    protected $table = 'test_tax_users';
    protected $guarded = [];
}

class TaxObjectTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('midtrans.server_key', 'SB-Mid-server-test');
        $app['config']->set('midtrans.is_sandbox', true);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->app['db']->connection()->getSchemaBuilder()->create('test_tax_users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->timestamps();
        });

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    protected function createTransaction(float $grossAmount = 100000): Transaction
    {
        $user = TaxUser::create(['name' => 'Test', 'email' => 'test@test.com']);

        return $user->transactions()->create([
            'order_id' => 'tax-' . uniqid(),
            'gross_amount' => $grossAmount,
            'status' => 'pending',
            'type' => 'one-time',
        ]);
    }

    /** @test */
    public function withTax_adds_tax_to_gross_amount(): void
    {
        $transaction = $this->createTransaction(100000);

        $transaction->update(['tax_amount' => 5000, 'gross_amount' => 105000]);

        $this->assertEquals(105000, (float) $transaction->fresh()->gross_amount);
        $this->assertEquals(5000, (float) $transaction->fresh()->tax_amount);
    }

    /** @test */
    public function withTax_adds_tax_item(): void
    {
        $transaction = $this->createTransaction(100000);

        $transaction->items()->create([
            'item_id' => 'tax',
            'price' => 5000,
            'quantity' => 1,
            'name' => 'Tax',
        ]);

        $item = $transaction->fresh()->items->firstWhere('item_id', 'tax');

        $this->assertNotNull($item);
        $this->assertEquals(5000, $item->price);
        $this->assertEquals('Tax', $item->name);
    }

    /** @test */
    public function withTaxPercentage_calculates_tax_from_gross_amount(): void
    {
        $transaction = $this->createTransaction(100000);

        $percent = 11;
        $expectedTax = 11000; // 100000 * 11%
        $expectedGross = 111000;

        $transaction->update([
            'gross_amount' => $expectedGross,
            'tax_amount' => $expectedTax,
            'tax_percentage' => $percent,
        ]);

        $fresh = $transaction->fresh();

        $this->assertEquals($expectedGross, (float) $fresh->gross_amount);
        $this->assertEquals($expectedTax, (float) $fresh->tax_amount);
        $this->assertEquals($percent, (float) $fresh->tax_percentage);
    }

    /** @test */
    public function withTaxPercentage_handles_decimal_result(): void
    {
        $transaction = $this->createTransaction(99500);

        $percent = 10;
        $expectedTax = 9950; // 99500 * 10% = 9950
        $expectedGross = 109450;

        $transaction->update([
            'gross_amount' => $expectedGross,
            'tax_amount' => $expectedTax,
            'tax_percentage' => $percent,
        ]);

        $fresh = $transaction->fresh();

        $this->assertEquals($expectedGross, (float) $fresh->gross_amount);
        $this->assertEquals($expectedTax, (float) $fresh->tax_amount);
    }

    /** @test */
    public function withTaxPercentage_adds_tax_item(): void
    {
        $transaction = $this->createTransaction(100000);

        $taxPrice = 10000; // 100000 * 10%

        $transaction->items()->create([
            'item_id' => 'tax',
            'price' => $taxPrice,
            'quantity' => 1,
            'name' => 'Tax',
        ]);

        $item = $transaction->fresh()->items->firstWhere('item_id', 'tax');

        $this->assertNotNull($item);
        $this->assertEquals($taxPrice, $item->price);
    }

    /** @test */
    public function withTax_zero_tax(): void
    {
        $transaction = $this->createTransaction(100000);

        $transaction->update([
            'gross_amount' => 100000,
            'tax_amount' => 0,
        ]);

        $this->assertEquals(100000, (float) $transaction->fresh()->gross_amount);
        $this->assertEquals(0, (float) $transaction->fresh()->tax_amount);
    }

    /** @test */
    public function withTaxPercentage_zero_percent(): void
    {
        $transaction = $this->createTransaction(100000);

        $transaction->update([
            'gross_amount' => 100000,
            'tax_amount' => 0,
            'tax_percentage' => 0,
        ]);

        $fresh = $transaction->fresh();

        $this->assertEquals(100000, (float) $fresh->gross_amount);
        $this->assertEquals(0, (float) $fresh->tax_amount);
        $this->assertEquals(0, (float) $fresh->tax_percentage);
    }

    /** @test */
    public function tax_amount_default_is_zero(): void
    {
        $transaction = $this->createTransaction(50000);

        $this->assertEquals(0, (float) $transaction->tax_amount);
        $this->assertEquals(0, (float) $transaction->tax_percentage);
    }

    /** @test */
    public function withTaxPercentage_100_percent(): void
    {
        $transaction = $this->createTransaction(50000);

        $transaction->update([
            'gross_amount' => 100000,
            'tax_amount' => 50000,
            'tax_percentage' => 100,
        ]);

        $fresh = $transaction->fresh();

        $this->assertEquals(100000, (float) $fresh->gross_amount);
        $this->assertEquals(50000, (float) $fresh->tax_amount);
        $this->assertEquals(100, (float) $fresh->tax_percentage);
    }
}
