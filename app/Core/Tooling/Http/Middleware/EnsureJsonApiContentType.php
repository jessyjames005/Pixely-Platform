<?php

declare(strict_types=1);

namespace App\Core\Tooling\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use Symfony\Component\HttpFoundation\Response;

final class EnsureJsonApiContentType
{
    public function handle(Request $request, Closure $next): Response
    {
        $contentType = strtolower(trim(explode(';', (string) $request->header('Content-Type'), 2)[0]));

        if ($contentType !== 'application/vnd.api+json') {
            throw JsonApiException::error([
                'status' => 415,
                'code' => 'UNSUPPORTED_MEDIA_TYPE',
                'title' => 'Unsupported Media Type',
                'detail' => 'This endpoint requires the application/vnd.api+json media type.',
            ]);
        }

        return $next($request);
    }
}
