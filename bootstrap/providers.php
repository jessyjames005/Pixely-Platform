<?php

use App\Providers\AppServiceProvider;
use App\Core\Providers\PixelyServiceProvider;
use App\Core\Auth\Providers\AuthServiceProvider;
use App\Core\Users\Providers\UserServiceProvider;
use App\Core\Roles\Providers\RoleServiceProvider;
use App\Core\Settings\Providers\SettingsServiceProvider;
use App\Core\Tooling\Providers\ToolingServiceProvider;
use App\Core\Extensions\Providers\ExtensionManagementServiceProvider;
use App\Providers\ExtensionServiceProvider;

return [
    AppServiceProvider::class,
    PixelyServiceProvider::class,
    AuthServiceProvider::class,
    UserServiceProvider::class,
    RoleServiceProvider::class,
    SettingsServiceProvider::class,
    ToolingServiceProvider::class,
    ExtensionManagementServiceProvider::class,
    ExtensionServiceProvider::class,
];
