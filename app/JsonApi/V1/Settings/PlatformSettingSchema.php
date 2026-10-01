<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Settings;

use App\JsonApi\V1\DocumentSchema;

final class PlatformSettingSchema extends DocumentSchema
{
    protected static string $resourceType = 'platform-settings';

    protected static array $attributeNames = ['site_name', 'locale'];
}
