<?php

/**
 * Translations extension manifest.
 */

declare(strict_types=1);

return [
    'id' => 'translations',
    'name' => 'Translations',
    'version' => '1.0.0',
    'surfaces' => ['admin', 'api'],
    'class' => App\Extensions\Translations\TranslationsExtension::class,
];
