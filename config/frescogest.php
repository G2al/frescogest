<?php

return [
    'whatsapp_number' => env('FRESCOGEST_WHATSAPP_NUMBER'),
    'admin' => [
        'name' => env('FRESCOGEST_ADMIN_NAME', 'Admin'),
        'email' => env('FRESCOGEST_ADMIN_EMAIL', 'admin@frescogest.it'),
        'password' => env('FRESCOGEST_ADMIN_PASSWORD'),
    ],
];
