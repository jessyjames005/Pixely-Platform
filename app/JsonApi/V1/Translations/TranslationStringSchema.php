<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Translations;

use App\JsonApi\V1\DocumentSchema;

final class TranslationStringSchema extends DocumentSchema
{
    protected static string $resourceType = 'translation-strings';

    protected static array $attributeNames = ['key', 'reference', 'target', 'suspect'];
}
