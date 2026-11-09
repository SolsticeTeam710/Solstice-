<?php

return [
    'server_key' => env('MIDTRANS_SERVER_KEY'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),
    'signature_secret_key' => env('MIDTRANS_SIGNATURE_SECRET_KEY'),
    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
];
