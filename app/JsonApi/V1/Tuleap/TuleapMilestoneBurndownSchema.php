<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Tuleap;

use App\JsonApi\V1\DocumentSchema;

final class TuleapMilestoneBurndownSchema extends DocumentSchema
{
    protected static string $resourceType = 'tuleap-milestone-burndowns';

    protected static array $attributeNames = ['totalPoints', 'startDate', 'endDate', 'actual', 'ideal'];
}
