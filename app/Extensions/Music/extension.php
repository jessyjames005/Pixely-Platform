<?php

declare(strict_types=1);

/**
 * Music extension manifest.
 */
return [
    'id' => 'music',
    'name' => 'Music',
    'version' => '1.0.0',
    'minimum_kernel_version' => '1.0.0',
    'surfaces' => ['admin', 'api'],
    'class' => App\Extensions\Music\MusicExtension::class,
];
