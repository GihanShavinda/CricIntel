<?php

use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(
    basePath: dirname(__DIR__)
)
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )

    /*
    |--------------------------------------------------------------------------
    | P8 - Broadcasting / Private Channel Authorization
    |--------------------------------------------------------------------------
    |
    | Creates:
    |
    | POST /api/broadcasting/auth
    |
    | Authentication is handled using Sanctum.
    |
    */

    ->withBroadcasting(
        __DIR__ . '/../routes/channels.php',
        [
            'prefix' => 'api',

            'middleware' => [
                'api',
                'auth:sanctum',
            ],
        ],
    )

    ->withMiddleware(function (
        Middleware $middleware
    ): void {

        /*
        |--------------------------------------------------------------------------
        | Sanctum SPA Authentication
        |--------------------------------------------------------------------------
        |
        | Allows React running on localhost:5173 to use Laravel Sanctum
        | cookie-based authentication.
        |
        */

        $middleware->statefulApi();
    })

    ->withExceptions(function (
        Exceptions $exceptions
    ): void {

        /*
        |--------------------------------------------------------------------------
        | API Authentication Errors
        |--------------------------------------------------------------------------
        |
        | Ensures unauthenticated API requests follow CricIntel's standard
        | ApiResponse structure:
        |
        | {
        |     "success": false,
        |     "message": "Unauthenticated."
        | }
        |
        */

        $exceptions->render(function (
            AuthenticationException $exception,
            Request $request
        ) {
            if (
                $request->is('api/*') ||
                $request->expectsJson()
            ) {
                return ApiResponse::error(
                    'Unauthenticated.',
                    401
                );
            }

            return null;
        });
    })

    ->create();
