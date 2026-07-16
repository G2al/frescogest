<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasCustomer
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->customer()->exists()) {
            return response()->json([
                'message' => 'Nessuna anagrafica cliente collegata all’account.',
            ], 403);
        }

        return $next($request);
    }
}
