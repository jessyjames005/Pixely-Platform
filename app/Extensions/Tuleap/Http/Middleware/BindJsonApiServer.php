<?php

declare(strict_types=1);

namespace App\Extensions\Tuleap\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Server\Server;
use LaravelJsonApi\Core\Facades\JsonApi;
use Symfony\Component\HttpFoundation\Response;

final class BindJsonApiServer
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->instance(Server::class, JsonApi::server('v1'));

        return $next($request);
    }
}
