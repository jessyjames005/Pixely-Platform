<?php

declare(strict_types=1);

namespace App\JsonApi\V1;

use App\JsonApi\V1\Photos\PhotoSchema;
use App\JsonApi\V1\Permissions\PermissionSchema;
use App\JsonApi\V1\Roles\RoleSchema;
use App\JsonApi\V1\Extensions\ExtensionConfigurationSchema;
use App\JsonApi\V1\Extensions\ExtensionSchema;
use App\JsonApi\V1\Files\FileSchema;
use App\JsonApi\V1\Settings\LocaleSchema;
use App\JsonApi\V1\Settings\PlatformSettingSchema;
use App\JsonApi\V1\Settings\UserSettingSchema;
use App\JsonApi\V1\Translations\TranslationCatalogSchema;
use App\JsonApi\V1\Translations\TranslationGroupSchema;
use App\JsonApi\V1\Translations\TranslationModuleSchema;
use App\JsonApi\V1\Translations\TranslationStringSchema;
use App\JsonApi\V1\Tooling\CacheKeySchema;
use App\JsonApi\V1\Tooling\DatabaseColumnSchema;
use App\JsonApi\V1\Tooling\DatabaseRowSchema;
use App\JsonApi\V1\Tooling\DatabaseTableSchema;
use App\JsonApi\V1\Tooling\LogEntrySchema;
use App\JsonApi\V1\Tooling\LogFileSchema;
use App\JsonApi\V1\CinemaMovie\CinemaMovieItemSchema;
use App\JsonApi\V1\Tuleap\TuleapBurndownCacheSchema;
use App\JsonApi\V1\Tuleap\TuleapCacheInfoSchema;
use App\JsonApi\V1\Tuleap\TuleapCafHistorySchema;
use App\JsonApi\V1\Tuleap\TuleapCafRecordSchema;
use App\JsonApi\V1\Tuleap\TuleapConfigSchema;
use App\JsonApi\V1\Tuleap\TuleapMilestoneBurndownSchema;
use App\JsonApi\V1\Tuleap\TuleapMilestoneSchema;
use App\JsonApi\V1\Tuleap\TuleapMilestoneStatsSchema;
use App\JsonApi\V1\Tuleap\TuleapPingSchema;
use App\JsonApi\V1\Tuleap\TuleapProjectMemberSchema;
use App\JsonApi\V1\Tuleap\TuleapProjectSchema;
use App\JsonApi\V1\Tuleap\TuleapRetroActionSchema;
use App\JsonApi\V1\Tuleap\TuleapSprintConfigSchema;
use App\JsonApi\V1\Tuleap\TuleapSprintHistorySchema;
use App\JsonApi\V1\Tuleap\TuleapTeamMemberSchema;
use App\JsonApi\V1\Users\UserSchema;
use LaravelJsonApi\Core\Server\Server as BaseServer;

final class Server extends BaseServer
{
    protected string $baseUri = '/api/v1';

    protected function allSchemas(): array
    {
        return [
            PhotoSchema::class,
            UserSchema::class,
            RoleSchema::class,
            PermissionSchema::class,
            PlatformSettingSchema::class,
            UserSettingSchema::class,
            LocaleSchema::class,
            TranslationCatalogSchema::class,
            TranslationModuleSchema::class,
            TranslationGroupSchema::class,
            TranslationStringSchema::class,
            ExtensionSchema::class,
            ExtensionConfigurationSchema::class,
            FileSchema::class,
            LogFileSchema::class,
            LogEntrySchema::class,
            CacheKeySchema::class,
            DatabaseTableSchema::class,
            DatabaseColumnSchema::class,
            DatabaseRowSchema::class,
            CinemaMovieItemSchema::class,
            TuleapProjectSchema::class,
            TuleapProjectMemberSchema::class,
            TuleapMilestoneSchema::class,
            TuleapMilestoneStatsSchema::class,
            TuleapMilestoneBurndownSchema::class,
            TuleapSprintHistorySchema::class,
            TuleapSprintConfigSchema::class,
            TuleapTeamMemberSchema::class,
            TuleapCafRecordSchema::class,
            TuleapCafHistorySchema::class,
            TuleapBurndownCacheSchema::class,
            TuleapRetroActionSchema::class,
            TuleapConfigSchema::class,
            TuleapCacheInfoSchema::class,
            TuleapPingSchema::class,
        ];
    }
}
