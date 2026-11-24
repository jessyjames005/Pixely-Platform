<?php

declare(strict_types=1);

namespace App\Extensions\Music\Http\Controllers\Api;

use Dedoc\Scramble\Attributes\Group;
use LaravelJsonApi\Core\Responses\DataResponse;

/**
 * Handles Music API requests.
 *
 * Returns strict JSON:API documents via LaravelJsonApi. Add methods
 * (index, store, show, update, destroy) following the same pattern as
 * App\Extensions\Gallery\Http\Controllers\Api\GalleryController,
 * and register the matching resource on the 'v1' JSON:API server.
 */
#[Group('Music', weight: 10)]
final class MusicController
{
    public function index(): DataResponse
    {
        return DataResponse::make([])
            ->withServer('v1')
            ->withMeta(['total' => 0]);
    }
}
