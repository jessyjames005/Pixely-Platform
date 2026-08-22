<?php

declare(strict_types=1);

namespace App\Core\Api\OpenApi\Paths\Gallery;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/v1/gallery',
    operationId: 'listGalleryPhotos',
    summary: 'List gallery photos',
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
            content: new OA\JsonContent(
                ref: '#/components/schemas/PhotoListResponse',
            ),
        ),
    ],
)]
final class GalleryList
{
}
