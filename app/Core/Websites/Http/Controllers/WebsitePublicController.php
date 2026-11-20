<?php

declare(strict_types=1);

namespace App\Core\Websites\Http\Controllers;

use App\Core\Websites\Contracts\WebsiteEngineInterface;
use App\Core\Websites\Services\PublicBlockSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;

final class WebsitePublicController extends Controller
{
    private const PAGE_TTL = 300;

    public function __construct(
        private readonly WebsiteEngineInterface $websiteEngine,
        private readonly PublicBlockSanitizer $blockSanitizer,
    ) {
    }

    public function page(string $slug): JsonResponse
    {
        $cacheKey = 'website:page:' . trim($slug, '/');

        $data = Cache::remember($cacheKey, self::PAGE_TTL, function () use ($slug): ?array {
            $page = $this->websiteEngine->getPublishedPage($slug);

            if ($page === null) {
                return null;
            }

            return [
                ...$page->jsonSerialize(),
                'blocks' => $this->blockSanitizer->sanitize($page->blocks),
            ];
        });

        if ($data === null) {
            return response()->json(['message' => 'Page not found'], 404);
        }

        return response()->json(['data' => $data]);
    }
}
