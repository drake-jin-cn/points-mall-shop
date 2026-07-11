<?php

use App\Http\Controllers\Internal\MenuItemController;
use App\Http\Middleware\InternalApiKeyMiddleware;
use App\Http\Middleware\JwtAuthMiddleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    $dbStatus = 'ok';
    try {
        DB::select('SELECT 1');
    } catch (Throwable $e) {
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

// Internal-only routes, called exclusively by the BFF service — never exposed to the frontend
// directly. Protected by INTERNAL_API_KEY instead of end-user JWT auth.
Route::prefix('internal/admin/menus')
    ->withoutMiddleware(JwtAuthMiddleware::class)
    ->middleware(InternalApiKeyMiddleware::class)
    ->group(function () {
        Route::get('/', [MenuItemController::class, 'index']);
        Route::post('/', [MenuItemController::class, 'store']);
        Route::put('/{id}', [MenuItemController::class, 'update']);
        Route::delete('/{id}', [MenuItemController::class, 'destroy']);
    });
