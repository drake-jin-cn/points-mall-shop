<?php

use App\Http\Middleware\JwtAuthMiddleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    $dbStatus = 'ok';
    try {
        DB::select('SELECT 1');
    } catch (\Throwable $e) {
        $dbStatus = 'error';
    }

    return response()->json([
        'status' => 'ok',
        'service' => 'points-mall-shop',
        'timestamp' => now()->utc()->toISOString(),
        'db' => $dbStatus,
        'uptime' => (int) round(microtime(true) - LARAVEL_START),
    ]);
})->withoutMiddleware(JwtAuthMiddleware::class);
