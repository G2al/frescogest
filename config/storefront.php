<?php

return [
    'daily_closure' => [
        'enabled' => env('STORE_DAILY_CLOSURE_ENABLED', true),
        'timezone' => env('STORE_DAILY_CLOSURE_TIMEZONE', env('APP_TIMEZONE', 'Europe/Rome')),
        'starts_at' => env('STORE_DAILY_CLOSURE_START', '10:00'),
        'ends_at' => env('STORE_DAILY_CLOSURE_END', '11:30'),
    ],
];
