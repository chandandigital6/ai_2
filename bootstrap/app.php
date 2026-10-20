<?php

use App\Http\Middleware\AcceptApiJson;
use App\Http\Middleware\AuthorizeBusinessModule;
use App\Http\Middleware\CapturePublicActivity;
use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\EnsureBusinessAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
