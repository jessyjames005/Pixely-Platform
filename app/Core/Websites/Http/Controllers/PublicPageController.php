<?php

declare(strict_types=1);

namespace App\Core\Websites\Http\Controllers;

use App\Core\Websites\Contracts\WebsiteEngineInterface;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

/**
 * Serves the public Vue application for published website pages only.
 *
 * Registered as the router fallback, so it never shadows a real route.
 * Reserved platform paths and unknown/unpublished pages get a real 404.
 */
final class PublicPageController
{
    private const RESERVED_PREFIXES = ['admin', 'account', 'api', 'docs', 'login', 'sanctum', 'up'];

    public function __construct(
        private readonly WebsiteEngineInterface $engine,
    ) {
    }

    public function __invoke(Request $request): View
    {
        $path = trim($request->path(), '/');
        $first = explode('/', $path)[0];

        if (in_array($first, self::RESERVED_PREFIXES, true)
            || $this->engine->getPublishedPage($path) === null) {
            abort(404);
        }

        return view('app');
    }
}
