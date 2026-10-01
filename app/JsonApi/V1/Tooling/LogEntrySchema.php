<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Tooling;

use App\JsonApi\V1\DocumentSchema;

final class LogEntrySchema extends DocumentSchema
{
    protected static string $resourceType = 'log-entries';

    protected static array $attributeNames = ['timestamp', 'level', 'message'];
}
