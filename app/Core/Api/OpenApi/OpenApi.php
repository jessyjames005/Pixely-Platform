<?php

declare(strict_types=1);

namespace App\Core\Api\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '0.1.0',
    title: 'Pixely Platform API',
    description: 'Public API for Pixely Platform extensions.',
)]
#[OA\Server(
    url: '/',
)]
#[OA\Tag(
    name: 'Gallery',
    description: 'Gallery photo management.',
)]
final class OpenApi
{
}
