<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Tuleap;

use App\JsonApi\V1\DocumentSchema;

final class TuleapMilestoneSchema extends DocumentSchema
{
    protected static string $resourceType = 'tuleap-milestones';

    protected static array $attributeNames = ['label', 'start_date', 'end_date', 'capacity', 'status', 'uri'];
}
