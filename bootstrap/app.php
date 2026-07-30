<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn ($request) => $request->is('api/*') ? null : route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function ($request, $throwable) {
            return $request->is('api/*') || $request->expectsJson();
        });

        $isApiRequest = fn ($request) => $request->is('api/*') || $request->expectsJson();

        // Route-model-binding "not found" (ModelNotFoundException) and unmatched
        // routes both surface here as NotFoundHttpException by the time render()
        // runs, so this one handler covers both without leaking model class names.
        $exceptions->render(function (NotFoundHttpException $e, $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json(['message' => 'Resource not found.'], 404);
            }
        });

        // AuthorizationException (from $this->authorize() / policy denial / the
        // 'can:' middleware) is converted to AccessDeniedHttpException before it
        // reaches here, so that's the type we catch.
        $exceptions->render(function (AccessDeniedHttpException $e, $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json(['message' => 'This action is unauthorized.'], 403);
            }
        });

        // Catch-all for truly unexpected errors. Only forced to a generic message
        // outside local debugging, so full traces are still visible while developing.
        $exceptions->render(function (Throwable $e, $request) use ($isApiRequest) {
            if ($isApiRequest($request) && ! config('app.debug')) {
                return response()->json(['message' => 'Server Error.'], 500);
            }
        });
    })->create();
