<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Tuleap;

use App\JsonApi\V1\DocumentSchema;

final class TuleapMilestoneStatsSchema extends DocumentSchema
{
    protected static string $resourceType = 'tuleap-milestone-stats';

    protected static array $attributeNames = [
        'total', 'done', 'devDone', 'byType', 'byTypeDone', 'byPerson', 'alerts', 'artifacts',
    ];
}
