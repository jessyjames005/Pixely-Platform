<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Tooling;

use App\JsonApi\V1\DocumentSchema;

final class DatabaseTableSchema extends DocumentSchema
{
    protected static string $resourceType = 'database-tables';

    protected static array $attributeNames = ['name', 'rows', 'size'];
}
