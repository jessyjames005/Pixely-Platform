<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Tuleap;

use App\JsonApi\V1\DocumentSchema;

final class TuleapRetroActionSchema extends DocumentSchema
{
    protected static string $resourceType = 'tuleap-retro-actions';

    protected static array $attributeNames = [
        'sprint_id', 'project_id', 'member_id', 'category', 'text', 'status', 'created_at',
    ];
}
