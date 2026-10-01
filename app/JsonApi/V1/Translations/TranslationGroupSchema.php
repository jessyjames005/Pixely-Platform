<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Translations;

use App\JsonApi\V1\DocumentSchema;

final class TranslationGroupSchema extends DocumentSchema
{
    protected static string $resourceType = 'translation-groups';

    protected static array $attributeNames = ['module', 'group', 'locale', 'translations', 'saved'];
}
