<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtAuthMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $token = $request->bearerToken();

        if (!$token) {
            return new JsonResponse([
                'code'    => 'shop-4001',
                'message' => 'Unauthorized',
                'data'    => null,
            ], 401);
        }

        try {
            $secret = env('JWT_SECRET');
            $payload = JWT::decode($token, new Key($secret, 'HS256'));
            $request->attributes->set('auth_user', $payload);
        } catch (\Exception $e) {
            return new JsonResponse([
                'code'    => 'shop-4001',
                'message' => 'Unauthorized',
                'data'    => null,
            ], 401);
        }

        return $next($request);
    }
}
