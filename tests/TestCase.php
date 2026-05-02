<?php

namespace Khumam\Midtrans\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Khumam\Midtrans\MidtransServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [MidtransServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('midtrans.server_key', 'SB-Mid-server-test');
        $app['config']->set('midtrans.is_sandbox', true);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
