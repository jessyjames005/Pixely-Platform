<?php

use App\Providers\AppServiceProvider;
use App\Core\Providers\PixelyServiceProvider;
use App\Providers\ExtensionServiceProvider;

return [
    AppServiceProvider::class,
    PixelyServiceProvider::class,
    ExtensionServiceProvider::class,
];
