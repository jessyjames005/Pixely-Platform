<?php

/**
 * CinemaMovie extension manifest.
 */

declare(strict_types=1);

return [
    'id' => 'cinema-movie',
    'name' => 'CinemaMovie',
    'version' => '1.0.0',
    'minimum_kernel_version' => '1.0.0',
    'surfaces' => ['admin', 'api'],
    'class' => App\Extensions\CinemaMovie\CinemaMovieExtension::class,
];
