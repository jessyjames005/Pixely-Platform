<?php

declare(strict_types=1);

use App\Extensions\CinemaMovie\CinemaMovieExtension;
use App\Core\Extensions\Translations\ExtensionTranslatableInterface;

it('declares the cinema-movie extension manifest', function () {
    $manifest = (new CinemaMovieExtension())->manifest();

    expect($manifest->id)->toBe('cinema-movie')
        ->and($manifest->name)->toBe('CinemaMovie')
        ->and($manifest->version)->toBe('1.1.0')
        ->and($manifest->minimum_kernel_version)->toBe('1.0.0');
});

it('declares its movie permissions and translations to the platform', function () {
    $extension = new CinemaMovieExtension();

    expect($extension->declaredPermissions())->toBe([
        'cinema-movie.movies.view',
        'cinema-movie.movies.manage',
        'cinema-movie.movies.delete',
    ])
        ->and($extension)->toBeInstanceOf(ExtensionTranslatableInterface::class);
});
