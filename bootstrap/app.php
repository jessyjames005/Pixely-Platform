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

        $middleware->prependToGroup('api', [
            'surface:api',
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
        ]);

        $middleware->alias([
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'surface' => \App\Core\Surface\Http\Middleware\ResolveSurface::class,
        ]);

        $middleware->redirectGuestsTo(fn (Request $request): string => '/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*'),
        );

        $exceptions->dontReport(
            \LaravelJsonApi\Core\Exceptions\JsonApiException::class,
        );
        $exceptions->render(
            \LaravelJsonApi\Exceptions\ExceptionParser::make()
                ->acceptsMiddleware('jsonapi:v1')
                ->renderable(),
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

            $title = match ($status) {
                404 => 'The requested resource was not found.',
                405 => 'This HTTP method is not allowed for this endpoint.',
                401 => 'Authentication is required to access this resource.',
                403 => 'You are not authorized to perform this action.',
                422 => 'The given data is invalid.',
                default => 'An unexpected error occurred.',
            };

            if ($exception instanceof ValidationException) {
                $errors = [];

                foreach ($exception->errors() as $field => $messages) {
                    foreach ($messages as $message) {
                        $errors[] = [
                            'status' => '422',
                            'code' => 'VALIDATION_ERROR',
                            'title' => $field,
                            'detail' => $message,
                            'source' => ['pointer' => '/data/attributes/' . str_replace('.', '/', $field)],
                        ];
                    }
                }

                return response()->json([
                    'jsonapi' => '1.1',
                    'errors' => $errors,
                ], $status, ['Content-Type' => 'application/vnd.api+json']);
            }

            return response()->json([
                'jsonapi' => '1.1',
                'errors' => [
                    [
                        'status' => (string) $status,
                        'code' => $code,
                        'title' => $title,
                        'detail' => $title,
                    ],
                ],
            ], $status, ['Content-Type' => 'application/vnd.api+json']);
        });
    })->create();
