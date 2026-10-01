<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Tuleap;

use App\JsonApi\V1\DocumentSchema;

final class TuleapConfigSchema extends DocumentSchema
{
    protected static string $resourceType = 'tuleap-configs';

    protected static array $attributeNames = ['tuleap_logged_in', 'tuleap_user_id'];
}
