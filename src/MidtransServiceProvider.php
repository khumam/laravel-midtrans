<?php

namespace Khumam\Midtrans;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MidtransServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/midtrans.php', 'midtrans'
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/midtrans.php' => config_path('midtrans.php'),
        ], 'midtrans-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'midtrans-migrations');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        Route::middleware('api')
            ->group(__DIR__ . '/../routes/webhook.php');
    }
}
