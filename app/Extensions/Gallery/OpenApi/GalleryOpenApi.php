<?php

declare(strict_types=1);

namespace App\Extensions\Gallery\OpenApi;

use OpenApi\Attributes as OA;

/**
 * OpenAPI definition for the Gallery extension.
 */
#[OA\Tag(
    name: 'Gallery',
    description: 'Gallery photo management.',
)]
final class GalleryOpenApi
{
}
