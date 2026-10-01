<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Tuleap;

use App\JsonApi\V1\DocumentSchema;

final class TuleapSprintConfigSchema extends DocumentSchema
{
    protected static string $resourceType = 'tuleap-sprint-configs';

    protected static array $attributeNames = [
        'objective', 'confidence_index', 'pct_evolution', 'pct_analysis', 'pct_bug',
        'working_days', 'velocity_per_day', 'review_comment',
    ];
}
