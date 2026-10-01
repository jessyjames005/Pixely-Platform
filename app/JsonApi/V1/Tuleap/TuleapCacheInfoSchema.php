<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Tuleap;

use App\JsonApi\V1\DocumentSchema;

final class TuleapCacheInfoSchema extends DocumentSchema
{
    protected static string $resourceType = 'tuleap-cache-info';

    protected static array $attributeNames = ['key', 'cached_at', 'expires_at', 'expired'];
}
