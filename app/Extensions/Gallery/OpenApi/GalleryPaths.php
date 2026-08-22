<?php

declare(strict_types=1);

namespace App\Extensions\Gallery\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Defines the Gallery API endpoints.
 */
final class GalleryPaths
{
    /**
     * List gallery photos.
     */
    #[OA\Get(
        path: '/api/v1/gallery',
        operationId: 'listGalleryPhotos',
        summary: 'List gallery photos',
        description: 'Display gallery photos.',
        tags: ['Gallery'],
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                description: 'Page number.',
                schema: new OA\Schema(
                    type: 'integer',
                    minimum: 1,
                    default: 1,
                ),
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                description: 'Number of photos per page. Maximum 100.',
                schema: new OA\Schema(
                    type: 'integer',
                    minimum: 1,
                    maximum: 100,
                    default: 20,
                ),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated gallery photos.',
            ),
        ],
    )]
    public function index(): void
    {
    }

    /**
     * Get a single gallery photo.
     */
    #[OA\Get(
        path: '/api/v1/gallery/{photo}',
        operationId: 'getGalleryPhoto',
        summary: 'Get a gallery photo',
        description: 'Display a single gallery photo.',
        tags: ['Gallery'],
        parameters: [
            new OA\Parameter(
                name: 'photo',
                in: 'path',
                required: true,
                description: 'Photo identifier.',
                schema: new OA\Schema(
                    type: 'integer',
                    example: 1,
                ),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Gallery photo.',
            ),
            new OA\Response(
                response: 404,
                description: 'Photo not found.',
            ),
        ],
    )]
    public function show(): void
    {
    }
}
