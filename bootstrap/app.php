<?php

use App\Http\Middleware\EnforceStoreOpeningHours;
use App\Http\Middleware\EnsureUserHasCustomer;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(
            fn (Request $request): string => $request->is('admin/*') ? '/admin/login' : '/login.html'
        );

        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'customer' => EnsureUserHasCustomer::class,
            'store.open' => EnforceStoreOpeningHours::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
