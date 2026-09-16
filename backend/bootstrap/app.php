<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*') || $exception instanceof HttpExceptionInterface) {
                return null;
            }

            return response()->json([
                'data' => null,
                'meta' => [
                    'errors' => [[
                        'code' => 'internal_error',
                        'message' => 'An unexpected error occurred.',
                    ]],
                    'request_id' => 'req_'.Str::ulid(),
                ],
                'message' => 'An unexpected error occurred.',
            ], 500);
        });
    })->create();
