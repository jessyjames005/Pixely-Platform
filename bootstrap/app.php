<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Session\Middleware\StartSession;

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
        // Enable Sanctum stateful API authentication for first-party clients.
        $middleware->statefulApi();

        // Keep API surface resolution in the API middleware group.
        $middleware->prependToGroup('api', 'surface:api');

        // Session support for API routes that explicitly require it.
        // Do not add CSRF middleware here: these remain API endpoints.
        $middleware->group('api.session', [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
        ]);

        $middleware->alias([
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'surface' => \App\Core\Surface\Http\Middleware\ResolveSurface::class,
        ]);

        $middleware->redirectGuestsTo(
            fn (Request $request): string => '/login',
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*'),
        );

        $exceptions->dontReport(
            \LaravelJsonApi\Core\Exceptions\JsonApiException::class,
        );

        // Preserve JSON:API validation error formatting and add Pixely's
        // required validation error code.
        $exceptions->render(function (
            ValidationException $exception,
            Request $request,
        ) {
            $response = \LaravelJsonApi\Exceptions\ExceptionParser::make()
                ->acceptsMiddleware('jsonapi:v1')
                ->render($exception, $request);

            if (! $response instanceof \Symfony\Component\HttpFoundation\Response) {
                return null;
            }

            $payload = json_decode((string) $response->getContent(), true);

            if (
                ! is_array($payload)
                || ! isset($payload['errors'])
                || ! is_array($payload['errors'])
            ) {
                return $response;
            }

            foreach ($payload['errors'] as $index => $error) {
                if (is_array($error) && ! isset($error['code'])) {
                    $payload['errors'][$index]['code'] = 'VALIDATION_ERROR';
                }
            }

            $response->setContent(json_encode($payload, JSON_THROW_ON_ERROR));

            return $response;
        });

        $exceptions->render(
            \LaravelJsonApi\Exceptions\ExceptionParser::make()
                ->acceptsMiddleware('jsonapi:v1')
                ->renderable(),
        );

        // Return the Registry-specific problem response for rate limits.
        $exceptions->render(function (
            \Illuminate\Http\Exceptions\ThrottleRequestsException $exception,
            Request $request,
        ) {
            if (! $request->routeIs('api.registry.*')) {
                return null;
            }

            return response()->json([
                'type' => 'https://registry.pixely.example/problems/rate-limited',
                'title' => 'Rate limit exceeded',
                'status' => 429,
                'detail' => 'The Registry request limit has been reached. Try again later.',
                'code' => 'rate_limited',
            ], 429, array_merge($exception->getHeaders(), [
                'Content-Type' => 'application/problem+json',
            ]));
        });

        // Provide the standard Pixely JSON:API error contract for API failures.
        $exceptions->renderable(function (
            \Throwable $exception,
            Request $request,
        ) {
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
                429 => 'TOO_MANY_REQUESTS',
                401 => 'UNAUTHENTICATED',
                403 => 'FORBIDDEN',
                422 => 'VALIDATION_ERROR',
                default => 'INTERNAL_SERVER_ERROR',
            };

            $title = match ($status) {
                404 => 'The requested resource was not found.',
                405 => 'This HTTP method is not allowed for this endpoint.',
                429 => 'Too many requests. Try again later.',
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
                            'source' => [
                                'pointer' => '/data/attributes/' . str_replace('.', '/', $field),
                            ],
                        ];
                    }
                }

                return response()->json([
                    'jsonapi' => '1.1',
                    'errors' => $errors,
                ], $status, [
                    'Content-Type' => 'application/vnd.api+json',
                ]);
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
            ], $status, [
                'Content-Type' => 'application/vnd.api+json',
            ]);
        });
    })
    ->create();
