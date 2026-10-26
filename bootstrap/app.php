<?php

use App\Http\Middleware\AcceptApiJson;
use App\Http\Middleware\AuthorizeBusinessModule;
use App\Http\Middleware\CapturePublicActivity;
use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\EnsureBusinessAccess;
use App\Http\Middleware\ThrottleByRoute;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            CapturePublicActivity::class,
            EnsureActiveAccount::class,
            AuthorizeBusinessModule::class,
        ]);

        $middleware->api(prepend: [AcceptApiJson::class], append: [EnsureActiveAccount::class]);
        $middleware->alias([
            'business.access' => EnsureBusinessAccess::class,
            'throttle' => ThrottleByRoute::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if ($request->expectsJson()) {
                $headers = $e->getHeaders();
                $retry = (int) ($headers['Retry-After'] ?? 60);

                return response()->json([
                    'message' => 'Too many requests. Please try again in '.$retry.' seconds.',
                    'error_code' => 'app_rate_limit',
                    'retry_after' => $retry,
                ], 429, $headers);
            }
        });
    })->create();
