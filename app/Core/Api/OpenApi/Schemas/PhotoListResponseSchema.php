<?php

declare(strict_types=1);

namespace App\Core\Api\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PhotoListResponse',
    type: 'object',
    required: ['data', 'meta'],
)]
final class PhotoListResponseSchema
{
    #[OA\Property(
        type: 'array',
        items: new OA\Items(
            ref: '#/components/schemas/Photo',
        ),
    )]
    public array $data;

    #[OA\Property(
        ref: '#/components/schemas/PaginationMeta',
    )]
    public object $meta;
}
