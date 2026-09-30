<?php

declare(strict_types=1);

namespace App\Extensions\CinemaMovie\Http\Controllers\Api;

use App\Core\Api\Response\ApiCollectionResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

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
    public function index(ApiCollectionResponse $apiResponse): JsonResponse
    {
        return $apiResponse->response(data: [], meta: ['total' => 0]);
    }
}
