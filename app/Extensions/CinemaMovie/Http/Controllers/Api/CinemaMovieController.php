<?php

declare(strict_types=1);

namespace App\Extensions\CinemaMovie\Http\Controllers\Api;

use Dedoc\Scramble\Attributes\Group;
use LaravelJsonApi\Core\Responses\DataResponse;

/**
 * Handles CinemaMovie API requests.
 *
 * This is an empty starting point — add methods (index, store,
 * show, update, destroy) following the same pattern as
 * App\Extensions\Gallery\Http\Controllers\Api\GalleryController.
 */
#[Group('CinemaMovie', weight: 10)]
final class CinemaMovieController
{
    /**
     * List cinema movies (starter endpoint).
     */
    public function index(): DataResponse
    {
        return DataResponse::make([])
            ->withServer('v1')
            ->withMeta(['total' => 0]);
    }
}
