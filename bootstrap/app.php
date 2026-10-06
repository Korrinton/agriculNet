<?php

use App\Http\Middleware\ComprobarCuentaActiva;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Modules\Admin\Http\Middleware\SoloAdministradores;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Permite que el frontend del propio dominio use /api con la sesión web
        $middleware->statefulApi();

        $middleware->web(append: [ComprobarCuentaActiva::class, ContentSecurityPolicy::class]);
        $middleware->api(append: [ComprobarCuentaActiva::class]);
        $middleware->alias(['admin' => SoloAdministradores::class]);

        // Baja de las alertas por correo con un clic (RFC 8058): la envía el cliente de correo, sin
        // token CSRF. La protege la firma del enlace (middleware signed).
        $middleware->preventRequestForgery(except: ['alertas/correo/baja/*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
