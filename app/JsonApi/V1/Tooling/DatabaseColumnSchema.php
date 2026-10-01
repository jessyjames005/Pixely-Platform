<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Tooling;

use App\JsonApi\V1\DocumentSchema;

final class DatabaseColumnSchema extends DocumentSchema
{
    protected static string $resourceType = 'database-columns';

    protected static array $attributeNames = ['name', 'type', 'nullable'];
}
