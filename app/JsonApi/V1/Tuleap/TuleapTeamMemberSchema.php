<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Tuleap;

use App\JsonApi\V1\DocumentSchema;

final class TuleapTeamMemberSchema extends DocumentSchema
{
    protected static string $resourceType = 'tuleap-team-members';

    protected static array $attributeNames = ['project_id', 'name', 'tuleap_username'];
}
