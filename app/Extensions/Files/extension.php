<?php

/**
 * Files extension manifest.
 */

declare(strict_types=1);

return [
    'id' => 'files',
    'name' => 'Files',
    'version' => '1.0.0',
    'surfaces' => ['admin', 'api'],
    'class' => App\Extensions\Files\FilesExtension::class,
];
