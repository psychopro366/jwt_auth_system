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
        
        // Register Cors middleware globally 
        $middleware->append([
            \App\Http\Middleware\Cors::class,
        ]);

        // Append ForceJsonResponse Middleware in 'api' group middleware 
        $middleware->appendToGroup('api', [
            \App\Http\Middleware\ForceJsonResponse::class,
        ]);

        // Registered ForceJsonResponse Middleware as route iddleware 
        $middleware->alias([
            'cors' => \App\Http\Middleware\Cors::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
