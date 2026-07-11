<?php

use App\Http\Middleware\JwtAuthMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        // This service exposes only internal endpoints consumed by the BFF (never a public
        // "api" surface), and BFF/render.yaml both assume unprefixed paths (/health,
        // /internal/admin/menus, ...) — disable Laravel's default "api" prefix to match.
        apiPrefix: '',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth.jwt' => JwtAuthMiddleware::class,
        ]);
        $middleware->appendToGroup('api', JwtAuthMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
