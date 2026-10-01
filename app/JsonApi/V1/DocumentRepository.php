<?php

declare(strict_types=1);

namespace App\JsonApi\V1;

use LaravelJsonApi\NonEloquent\AbstractRepository;

final class DocumentRepository extends AbstractRepository
{
    public function find(string $resourceId): ?object
    {
        return (object) [
            'id' => $resourceId,
            'attributes' => [],
        ];
    }
}
