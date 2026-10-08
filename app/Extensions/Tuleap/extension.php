<?php

declare(strict_types=1);

return [
    'id' => 'tuleap',
    'name' => 'Tuleap',
    'version' => '1.0.0',
    'surfaces' => ['admin', 'api'],
    'class' => App\Extensions\Tuleap\TuleapExtension::class,
];