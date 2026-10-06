<?php

declare(strict_types=1);

return [
    App\Core\Auth\Providers\AuthServiceProvider::class,
    App\Core\Extensions\Providers\ExtensionManagementServiceProvider::class,
    App\Core\Providers\PixelyServiceProvider::class,
    App\Core\Roles\Providers\RoleServiceProvider::class,
    App\Core\Settings\Providers\SettingsServiceProvider::class,
    App\Core\Tooling\Providers\ToolingServiceProvider::class,
    App\Core\Translations\Providers\CoreTranslationsServiceProvider::class,
    App\Core\Users\Providers\UserServiceProvider::class,
    App\Core\Websites\Providers\WebsiteEngineServiceProvider::class,
    App\Providers\AppServiceProvider::class,
    App\Providers\ExtensionServiceProvider::class,
    ...(class_exists(\Laravel\Telescope\TelescopeApplicationServiceProvider::class)
        ? [App\Providers\TelescopeServiceProvider::class]
        : []),
];
