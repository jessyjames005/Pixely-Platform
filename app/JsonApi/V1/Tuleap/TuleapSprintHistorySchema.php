<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Tuleap;

use App\JsonApi\V1\DocumentSchema;

final class TuleapSprintHistorySchema extends DocumentSchema
{
    protected static string $resourceType = 'tuleap-sprint-history';

    protected static array $attributeNames = [
        'label', 'start_date', 'end_date', 'engagement', 'totalPoints', 'donePoints',
        'initialCommitment', 'commitmentPoints', 'commitmentDone', 'totalCount',
        'doneCount', 'addedCount', 'cafTotal', 'capacity', 'predictability', 'commitmentRespect',
    ];
}
