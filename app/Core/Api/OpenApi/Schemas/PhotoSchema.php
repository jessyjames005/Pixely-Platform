<?php

declare(strict_types=1);

namespace App\Core\Api\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Photo',
    type: 'object',
    required: ['id', 'title', 'filename'],
)]
final class PhotoSchema
{
    #[OA\Property(
        type: 'integer',
        example: 1,
    )]
    public int $id;

    #[OA\Property(
        type: 'string',
        nullable: true,
        example: 'Sunset',
    )]
    public ?string $title;

    #[OA\Property(
        type: 'string',
        example: 'gallery/sunset.jpg',
    )]
    public string $filename;
}
