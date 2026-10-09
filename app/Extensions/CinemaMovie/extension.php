<?php

declare(strict_types=1);

/**
 * CinemaMovie extension manifest.
 */

return [
    'id' => 'cinema-movie',
    'name' => 'CinemaMovie',
    'version' => '1.0.0',
    'minimum_kernel_version' => '1.0.0',
    'surfaces' => ['admin', 'api'],
    'class' => App\Extensions\CinemaMovie\CinemaMovieExtension::class,
];
