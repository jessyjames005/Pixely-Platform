<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Tuleap;

use App\JsonApi\V1\DocumentSchema;

final class TuleapProjectSchema extends DocumentSchema
{
    protected static string $resourceType = 'tuleap-projects';

    protected static array $attributeNames = ['label', 'shortname', 'is_member_of', 'uri'];
}
