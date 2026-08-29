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
        $middleware->statefulApi();

        $middleware->alias([
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        ]);
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

                $exception instanceof \Illuminate\Auth\AuthenticationException => 401,

                $exception instanceof \Illuminate\Auth\Access\AuthorizationException => 403,

                $exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                => $exception->getStatusCode(),

                default => 500,
            };

            $code = match ($status) {
                404 => 'RESOURCE_NOT_FOUND',
                405 => 'METHOD_NOT_ALLOWED',
                401 => 'UNAUTHENTICATED',
                403 => 'FORBIDDEN',
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
                    'message' => match ($status) {
                        404 => 'The requested resource was not found.',
                        405 => 'This HTTP method is not allowed for this endpoint.',
                        401 => 'Authentication is required to access this resource.',
                        403 => 'You are not authorized to perform this action.',
                        default => 'An unexpected error occurred.',
                    },
                ],
            ], $status);
        });
    })->create();
