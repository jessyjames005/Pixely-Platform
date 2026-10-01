<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Settings;

use App\JsonApi\V1\DocumentSchema;

final class LocaleSchema extends DocumentSchema
{
    protected static string $resourceType = 'locales';

    protected static array $attributeNames = ['code', 'label'];
}
