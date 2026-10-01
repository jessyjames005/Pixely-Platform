<?php

declare(strict_types=1);

return [
    'namespace' => 'JsonApi',
    'servers' => [
        'v1' => App\JsonApi\V1\Server::class,
    ],
];
