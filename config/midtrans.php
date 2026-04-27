<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Midtrans Server Key
    |--------------------------------------------------------------------------
    |
    | Your Midtrans server key, used for API calls and webhook validation.
    | Found in Midtrans Dashboard > Settings > Access Keys.
    |
    */

    'server_key' => env('MIDTRANS_SERVER_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Midtrans Sandbox Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, requests go to the Midtrans sandbox environment.
    |
    */

    'is_sandbox' => env('MIDTRANS_IS_SANDBOX', true),

    /*
    |--------------------------------------------------------------------------
    | Midtrans webhook URL
    |--------------------------------------------------------------------------
    |
    | The route name for the webhook endpoint that Midtrans will call.
    |
    */

    'webhook_url' => 'midtrans/webhook',

];
