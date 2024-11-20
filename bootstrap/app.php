<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::prefix('device')
                ->group(base_path('routes/device.php'));
        },
        // apiPrefix: 'admin',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->appendToGroup('login', [
            \App\Http\Middleware\Login::class,
        ]);

        $middleware->appendToGroup('checkIp', [
            \App\Http\Middleware\CheckIp::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
