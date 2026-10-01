<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Translations;

use App\JsonApi\V1\DocumentSchema;

final class TranslationCatalogSchema extends DocumentSchema
{
    protected static string $resourceType = 'translation-catalogs';

    protected static array $attributeNames = ['locale', 'catalog'];
}
