<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Tuleap;

use App\JsonApi\V1\DocumentSchema;

final class TuleapProjectMemberSchema extends DocumentSchema
{
    protected static string $resourceType = 'tuleap-project-members';

    protected static array $attributeNames = ['display_name', 'username'];
}
