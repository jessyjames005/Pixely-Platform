<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Tooling;

use App\JsonApi\V1\DocumentSchema;

final class CacheKeySchema extends DocumentSchema
{
    protected static string $resourceType = 'cache-keys';

    protected static array $attributeNames = ['key', 'type', 'ttl', 'value'];
}
