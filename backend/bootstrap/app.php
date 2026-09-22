<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        $middleware->encryptCookies(except: ['noure_cart']);
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'data' => null,
                'meta' => ['errors' => [[
                    'code' => 'unauthenticated',
                    'message' => 'Authentication is required.',
                ]]],
                'message' => 'Authentication is required.',
            ], 401);
        });

        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*') || $exception instanceof HttpExceptionInterface) {
                return null;
            }

            $requestId = $request->attributes->get('request_id') ?? 'req_'.Str::ulid();
            $request->attributes->set('request_id', $requestId);
            Log::error('Unhandled API exception', [
                'request_id' => $requestId,
                'route' => $request->route()?->getName() ?? $request->path(),
                'exception' => $exception::class,
                'shipping_method_code' => $request->input('shipping_method_code'),
                'address_public_id' => $request->input('address_public_id'),
                'order_public_id' => $request->route('order_public_id'),
            ]);

            return response()->json([
                'data' => null,
                'meta' => [
                    'errors' => [[
                        'code' => 'internal_error',
                        'message' => 'An unexpected error occurred.',
                    ]],
                    'request_id' => $requestId,
                ],
                'message' => 'An unexpected error occurred.',
            ], 500);
        });
    })->create();
