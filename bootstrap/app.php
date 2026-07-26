<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust only RFC1918 private-network ranges, not '*'. This app's only
        // internet-facing service is the `nginx` container (docker-compose.yml);
        // `app` has no published host port and is reachable exclusively from
        // other containers on the internal `nexus_network` bridge network, which
        // always falls in these private ranges. Trusting '*' let a client-supplied
        // X-Forwarded-For header defeat the login rate limiter, which keys on
        // $request->ip() (see app/Http/Requests/Auth/LoginRequest.php).
        $middleware->trustProxies(at: [
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
        ]);
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
