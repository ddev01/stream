<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'dev.api' => \App\Http\Middleware\DevelopmentApiAuth::class,
        ]);

        // Allow the browser to set a plain (unencrypted) timezone cookie so we can
        // render datetimes in the viewer's timezone (guests included).
        $middleware->encryptCookies(except: [
            'timezone',
        ]);

        // Redirect guests to /login instead of route('login')
        $middleware->redirectGuestsTo('/login');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        if (app()->environment('production')) {
            Integration::handles($exceptions);
        }
    })->create();
