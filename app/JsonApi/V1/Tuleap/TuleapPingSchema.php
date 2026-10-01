<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Tuleap;

use App\JsonApi\V1\DocumentSchema;

final class TuleapPingSchema extends DocumentSchema
{
    protected static string $resourceType = 'tuleap-ping-snapshots';

    protected static array $attributeNames = ['ok', 'status', 'message', 'httpStatus'];
}
