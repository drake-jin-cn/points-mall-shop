<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Mirrors points-mall-core's InternalApiKeyFilter: constant-time comparison, no default value
// for the expected key (misconfiguration must fail closed, not open), 401 on any mismatch.
class InternalApiKeyMiddleware
{
    private const HEADER_NAME = 'INTERNAL_API_KEY';

    public function handle(Request $request, Closure $next): mixed
    {
        $expectedKey = env('INTERNAL_API_KEY');
        $providedKey = $request->header(self::HEADER_NAME);

        $keyValid = is_string($expectedKey)
            && $expectedKey !== ''
            && is_string($providedKey)
            && hash_equals($expectedKey, $providedKey);

        if (! $keyValid) {
            return new JsonResponse([
                'code' => 'shop-4013',
                'message' => 'Unauthorized internal caller',
                'data' => null,
            ], 401);
        }

        return $next($request);
    }
}
