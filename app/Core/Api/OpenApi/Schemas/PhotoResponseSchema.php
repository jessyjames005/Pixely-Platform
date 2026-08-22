<?php

declare(strict_types=1);

namespace App\Core\Api\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PhotoResponse',
    type: 'object',
    required: ['data'],
)]
final class PhotoResponseSchema
{
    #[OA\Property(
        ref: '#/components/schemas/Photo',
    )]
    public object $data;
}
