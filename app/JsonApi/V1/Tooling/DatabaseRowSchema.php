<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Tooling;

use App\JsonApi\V1\DocumentSchema;

final class DatabaseRowSchema extends DocumentSchema
{
    protected static string $resourceType = 'database-rows';

    protected static array $attributeNames = ['values'];
}
