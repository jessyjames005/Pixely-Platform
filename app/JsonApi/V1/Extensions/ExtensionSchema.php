<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Extensions;

use App\JsonApi\V1\DocumentSchema;

final class ExtensionSchema extends DocumentSchema
{
    protected static string $resourceType = 'extensions';

    protected static array $attributeNames = ['name', 'version', 'dependencies', 'enabled'];
}
