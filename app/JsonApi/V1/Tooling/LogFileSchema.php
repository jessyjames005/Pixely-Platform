<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Tooling;

use App\JsonApi\V1\DocumentSchema;

final class LogFileSchema extends DocumentSchema
{
    protected static string $resourceType = 'log-files';

    protected static array $attributeNames = ['filename', 'size', 'modified_at'];
}
