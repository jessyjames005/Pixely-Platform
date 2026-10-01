<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Settings;

use App\JsonApi\V1\DocumentSchema;

final class UserSettingSchema extends DocumentSchema
{
    protected static string $resourceType = 'user-settings';

    protected static array $attributeNames = ['locale', 'theme', 'density', 'email_notifications'];
}
