<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class StorefrontPageController extends Controller
{
    private const PAGES = [
        'cart.html',
        'catalog.html',
        'forgot-password.html',
        'index.html',
        'login.html',
        'orders.html',
        'product.html',
        'profile.html',
        'register.html',
        'reset-password.html',
        'whatsapp.html',
    ];

    public function __invoke(string $page): Response
    {
        abort_unless(in_array($page, self::PAGES, true), 404);

        return response(file_get_contents(public_path($page)), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-cache, private',
        ]);
    }
}
