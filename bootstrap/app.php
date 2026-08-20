<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__ . '/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*'),
        );

        $exceptions->renderable(function (\Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = match (true) {
                $exception instanceof ValidationException => 422,

                $exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                => $exception->getStatusCode(),

                default => 500,
            };

            $code = match ($status) {
                404 => 'RESOURCE_NOT_FOUND',
                422 => 'VALIDATION_ERROR',
                default => 'INTERNAL_SERVER_ERROR',
            };

            if ($exception instanceof ValidationException) {
                return response()->json([
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'The given data was invalid.',
                        'details' => $exception->errors(),
                    ],
                ], 422);
            }

            return response()->json([
                'error' => [
                    'code' => $code,
                    'message' => $status === 404
                        ? 'The requested resource was not found.'
                        : 'An unexpected error occurred.',
                ],
            ], $status);
        });
    })->create();
