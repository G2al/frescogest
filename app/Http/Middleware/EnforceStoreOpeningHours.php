<?php

namespace App\Http\Middleware;

use App\Services\Storefront\StoreOpeningHours;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceStoreOpeningHours
{
    public function __construct(private readonly StoreOpeningHours $openingHours) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->openingHours->isClosed()) {
            return $next($request);
        }

        $status = $this->openingHours->status();
        $retryAfter = max(1, (int) ceil(
            CarbonImmutable::parse($status['server_time'])
                ->diffInSeconds(CarbonImmutable::parse($status['reopens_at']))
        ));

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Il negozio è temporaneamente chiuso per l’aggiornamento quotidiano di prezzi e disponibilità.',
                'data' => $status,
            ], Response::HTTP_SERVICE_UNAVAILABLE, [
                'Cache-Control' => 'no-store, private',
                'Retry-After' => (string) $retryAfter,
            ]);
        }

        $html = file_get_contents(resource_path('storefront/store-closed.html'));
        $html = str_replace(
            ['__CLOSURE_START__', '__REOPENING_AT__', '__SERVER_TIME__', '__REOPENING_TIME__'],
            [
                json_encode($status['closes_at']),
                json_encode($status['reopens_at']),
                json_encode($status['server_time']),
                CarbonImmutable::parse($status['reopens_at'])
                    ->setTimezone((string) config('storefront.daily_closure.timezone'))
                    ->format('H:i'),
            ],
            $html,
        );

        return response($html, Response::HTTP_SERVICE_UNAVAILABLE, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
            'Retry-After' => (string) $retryAfter,
        ]);
    }
}
