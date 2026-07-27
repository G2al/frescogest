<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasActiveEmployee
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('employee');
        $employee = $user?->employee;

        if (! $user?->active || ! $employee?->active) {
            auth('employee')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('employee.login')
                ->withErrors(['email' => 'Questo account dipendente non è attivo.']);
        }

        return $next($request);
    }
}
