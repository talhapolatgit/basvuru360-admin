<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Coolify/Traefik terminates TLS; trust X-Forwarded-* so asset()/url() stay https.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'yetki' => \App\Http\Middleware\CheckYetki::class,
            'kurs.kapsam' => \App\Http\Middleware\CheckKursKapsami::class,
            'etkinlik.kapsam' => \App\Http\Middleware\CheckEtkinlikKapsami::class,
            'merkez.kapsam' => \App\Http\Middleware\CheckMerkezKapsami::class,
            'jwt' => \App\Http\Middleware\AuthenticateJwt::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request, \Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Doğrulama hatası.',
                'errors' => $e->errors(),
            ], $e->status);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Yetkilendirme gerekli.',
                'errors' => null,
            ], 401);
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'İstek işlenemedi.',
                'errors' => null,
            ], $e->getStatusCode());
        });
    })->create();
