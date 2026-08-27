<?php

use App\Providers\AppServiceProvider;
use App\Core\Providers\PixelyServiceProvider;
use App\Core\Auth\Providers\AuthServiceProvider;
use App\Core\Users\Providers\UserServiceProvider;
use App\Core\Roles\Providers\RoleServiceProvider;
use App\Providers\ExtensionServiceProvider;

return [
    AppServiceProvider::class,
    PixelyServiceProvider::class,
    AuthServiceProvider::class,
    UserServiceProvider::class,
    RoleServiceProvider::class,
    ExtensionServiceProvider::class,
];
