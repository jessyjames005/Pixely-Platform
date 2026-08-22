<?php

declare(strict_types=1);

namespace App\Core\Api\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ApiError',
    type: 'object',
    required: ['error'],
)]
final class ApiErrorSchema
{
    #[OA\Property(
        type: 'object',
        required: ['code', 'message'],
        properties: [
            new OA\Property(
                property: 'code',
                type: 'string',
                example: 'RESOURCE_NOT_FOUND',
            ),
            new OA\Property(
                property: 'message',
                type: 'string',
                example: 'The requested resource was not found.',
            ),
            new OA\Property(
                property: 'details',
                type: 'object',
                nullable: true,
                additionalProperties: true,
            ),
        ],
    )]
    public object $error;
}
