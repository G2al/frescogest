<?php

return [
    'daily_closure' => [
        'enabled' => env('STORE_DAILY_CLOSURE_ENABLED', true),
        'timezone' => env('STORE_DAILY_CLOSURE_TIMEZONE', env('APP_TIMEZONE', 'Europe/Rome')),
    ],
];
