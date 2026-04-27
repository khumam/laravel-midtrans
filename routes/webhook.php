<?php

use Illuminate\Support\Facades\Route;
use Khumam\Midtrans\Http\Controllers\WebhookController;

Route::post(config('midtrans.webhook_url', 'midtrans/webhook'), [WebhookController::class, 'handleWebhook']);
