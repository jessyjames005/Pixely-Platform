<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Middleware;

use Closure;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use Symfony\Component\HttpFoundation\Response;

final class EnsureJsonApiMediaType
{
    public function handle(Request $request, Closure $next): Response
    {
        $acceptable = $request->getAcceptableContentTypes();
        $acceptsJsonApi = $acceptable === []
            || in_array('application/vnd.api+json', $acceptable, true)
            || in_array('application/*', $acceptable, true)
            || in_array('*/*', $acceptable, true)
            || in_array('*', $acceptable, true);

        if (! $acceptsJsonApi) {
            throw JsonApiException::error([
                'status' => 406,
                'code' => 'NOT_ACCEPTABLE',
                'detail' => 'This endpoint only produces the application/vnd.api+json media type.',
            ]);
        }

        return $next($request);
    }
}
