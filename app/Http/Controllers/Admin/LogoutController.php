<?php

namespace App\Http\Controllers\Admin;

use Filament\Auth\Http\Responses\Contracts\LogoutResponse;
use Filament\Facades\Filament;

class LogoutController
{
    public function __invoke(): LogoutResponse
    {
        Filament::auth()->logout();

        session()->migrate(true);
        session()->regenerateToken();

        return app(LogoutResponse::class);
    }
}
