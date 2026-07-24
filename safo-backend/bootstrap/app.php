<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // All API requests should render as JSON
        $exceptions->shouldRenderJsonWhen(function ($request, \Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        // Auth: not logged in
        $exceptions->renderable(function (AuthenticationException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => 'غير مسجل الدخول',
            ], 401);
        });

        // Auth: authorization denied (Gate/Policy)
        $exceptions->renderable(function (AuthorizationException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'ليس لديك صلاحية للوصول',
            ], 403);
        });

        // HTTP: access denied
        $exceptions->renderable(function (AccessDeniedHttpException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للوصول',
            ], 403);
        });

        // HTTP: not found
        $exceptions->renderable(function (NotFoundHttpException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => 'الموارد غير موجودة',
            ], 404);
        });

        // Business logic exceptions
        $exceptions->renderable(function (\App\Exceptions\OrderCreationException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        });

        $exceptions->renderable(function (\InvalidArgumentException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        });
    })->create();
