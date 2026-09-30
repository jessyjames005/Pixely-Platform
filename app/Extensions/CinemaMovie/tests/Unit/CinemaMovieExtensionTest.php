<?php

declare(strict_types=1);

use App\Extensions\CinemaMovie\CinemaMovieExtension;

it('declares the cinema-movie extension manifest', function () {
    $manifest = (new CinemaMovieExtension())->manifest();

    expect($manifest->id)->toBe('cinema-movie')
        ->and($manifest->name)->toBe('CinemaMovie')
        ->and($manifest->minimum_kernel_version)->toBe('1.0.0');
});
