<?php

declare(strict_types=1);

namespace App\Core\Websites\Http\Controllers;

use App\Core\Websites\Contracts\WebsiteEngineInterface;
use App\Core\Websites\Services\PublicBlockSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Exposes published website content to the public surface.
 *
 * Draft and archived pages are filtered by the Website Engine so the
 * public surface never becomes an alternative administration endpoint.
 */
final class WebsitePublicController extends Controller
{
    public function __construct(
        private readonly WebsiteEngineInterface $websiteEngine,
        private readonly PublicBlockSanitizer $blockSanitizer,
    ) {
    }

    public function page(string $slug): JsonResponse
    {
        $page = $this->websiteEngine->getPublishedPage($slug);

        if ($page === null) {
            return response()->json(['message' => 'Page not found'], 404);
        }

        return response()->json(['data' => [
            ...$page->jsonSerialize(),
            'blocks' => $this->blockSanitizer->sanitize($page->blocks),
        ]]);
    }
}
