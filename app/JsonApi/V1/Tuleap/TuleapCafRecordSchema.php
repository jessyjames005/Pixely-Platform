<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Tuleap;

use App\JsonApi\V1\DocumentSchema;

final class TuleapCafRecordSchema extends DocumentSchema
{
    protected static string $resourceType = 'tuleap-caf-records';

    protected static array $attributeNames = ['sprint_id', 'member_id', 'value', 'name', 'tuleap_username'];
}
