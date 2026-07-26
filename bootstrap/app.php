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
        // Trust no proxies. docker/nginx/default.conf's PHP location block does
        // `include fastcgi_params;`, and the standard fastcgi_params file sets
        // `fastcgi_param REMOTE_ADDR $remote_addr;` — so PHP-FPM always sees the
        // TRUE client IP that connected to nginx, not nginx's own container
        // address. nginx is the internet-facing edge in this deployment (see
        // docker-compose.yml: only `nginx` publishes a host port); there is no
        // reverse proxy in front of it, so there is no legitimate source of a
        // trustworthy X-Forwarded-For header at all. Trusting any range —
        // including RFC1918 private ranges — let a client whose own IP happens
        // to fall in that range (e.g. anyone on a LAN/NAT) supply a spoofed
        // X-Forwarded-For and defeat the login rate limiter, which keys on
        // $request->ip() (see app/Http/Requests/Auth/LoginRequest.php).
        //
        // If a real reverse proxy/CDN/load-balancer is ever placed in front of
        // nginx, trust exactly that proxy's specific IP/CIDR here (never a
        // broad private range or '*'), and confirm it overwrites
        // X-Forwarded-For rather than appending to client-supplied values.
        $middleware->trustProxies(at: []);
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
