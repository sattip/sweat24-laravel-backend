<?php

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
        // Enable CORS for API requests
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
        
        // Use custom CSRF middleware that excludes API routes
        $middleware->web(replace: [
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class => \App\Http\Middleware\VerifyCsrfToken::class,
        ]);
        
        // Register admin middleware alias
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'role' => \App\Http\Middleware\CheckRole::class,
            'debug' => \App\Http\Middleware\DebugMiddleware::class,
        ]);
        
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
