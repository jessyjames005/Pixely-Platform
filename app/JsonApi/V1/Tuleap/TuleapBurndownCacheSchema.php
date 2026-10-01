<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Tuleap;

use App\JsonApi\V1\DocumentSchema;

final class TuleapBurndownCacheSchema extends DocumentSchema
{
    protected static string $resourceType = 'tuleap-burndown-caches';

    protected static array $attributeNames = ['sprint_id', 'points'];
}
