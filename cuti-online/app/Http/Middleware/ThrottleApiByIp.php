<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ThrottleApiByIp
{
    private const REQUESTS_PER_MINUTE = 120;

    public function __construct(private readonly RateLimiter $limiter) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('api/v1', 'api/v1/*')) {
            return $next($request);
        }

        $key = 'api-v1-ip:'.hash('sha256', $request->ip() ?? '');
        if ($this->limiter->tooManyAttempts($key, self::REQUESTS_PER_MINUTE)
            || $this->limiter->hit($key) > self::REQUESTS_PER_MINUTE) {
            return response()->json(
                ['message' => 'Terlalu banyak permintaan. Coba lagi nanti.'],
                429,
                ['Retry-After' => max(1, $this->limiter->availableIn($key))],
            );
        }

        return $next($request);
    }
}
