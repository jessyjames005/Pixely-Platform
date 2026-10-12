<?php

/**
 * Gallery extension manifest (test fixture only).
 */

declare(strict_types=1);

return [

    'id' => 'gallery',
    'name' => 'gallery',
    'version' => '1.0.0',
    'class' => App\Extensions\Gallery\GalleryExtension::class,
    'surfaces' => ['public', 'admin'],
    'requires' => [
        'media',
    ],
];
