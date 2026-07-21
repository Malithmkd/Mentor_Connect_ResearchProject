<?php

use App\Http\Middleware\RequireApproval;
use App\Http\Middleware\RequireRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/**
 * Laravel 11 Application Bootstrap
 * Simplified structure: routing, middleware, and exceptions configured here.
 */

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Custom middleware aliases
        $middleware->alias([
            'role'     => RequireRole::class,
            'approved' => RequireApproval::class,
        ]);

        // Append approval gate to the 'web' middleware group so every
        // authenticated web request is checked automatically.
        $middleware->appendToGroup('web', RequireApproval::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Redirect authorization failures back with an error message
        $exceptions->renderable(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            return redirect()->back()->with('error', 'You do not have permission to perform this action.');
        });
    })
    ->create();
