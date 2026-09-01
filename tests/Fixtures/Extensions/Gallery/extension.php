<?php

declare(strict_types=1);

/**
 * Gallery extension manifest (test fixture only).
 */
return [
    'id' => 'gallery',
    'name' => 'gallery',
    'version' => '1.0.0',
    'class' => Tests\Fakes\Extensions\GalleryExtension::class,
    'requires' => [
        'media',
    ],
];
