<?php

return [
    'environment' => env('MPESA_ENV', 'sandbox'),
    'consumer_key' => env('MPESA_CONSUMER_KEY'),
    'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
    'passkey' => env('MPESA_PASSKEY'),
    'short_code' => env('MPESA_SHORT_CODE'),
    'callback_url' => 'https://2e34ead255ea.ngrok-free.app/api/mpesa/callback',
    'timeout' => env('MPESA_TIMEOUT', 60),
    'transaction_type' => env('MPESA_TRANSACTION_TYPE', 'CustomerPayBillOnline'),
    'verify_ssl' => env('MPESA_VERIFY_SSL', false),
];
