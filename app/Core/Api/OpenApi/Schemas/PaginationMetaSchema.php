<?php

declare(strict_types=1);

namespace App\Core\Api\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PaginationMeta',
    type: 'object',
    required: ['current_page', 'last_page', 'per_page', 'total'],
)]
final class PaginationMetaSchema
{
    #[OA\Property(type: 'integer', example: 1)]
    public int $currentPage;

    #[OA\Property(type: 'integer', example: 5)]
    public int $lastPage;

    #[OA\Property(type: 'integer', example: 20)]
    public int $perPage;

    #[OA\Property(type: 'integer', example: 95)]
    public int $total;
}
