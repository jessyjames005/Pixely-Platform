<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Extensions;

use App\JsonApi\V1\DocumentSchema;

final class ExtensionConfigurationSchema extends DocumentSchema
{
    protected static string $resourceType = 'extension-configurations';

    protected static array $attributeNames = ['defaults', 'values'];
}
