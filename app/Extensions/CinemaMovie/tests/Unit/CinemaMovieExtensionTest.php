<?php

declare(strict_types=1);

use App\Extensions\CinemaMovie\CinemaMovieExtension;
use App\Core\Extensions\Translations\ExtensionTranslatableInterface;

it('declares the cinema-movie extension manifest', function () {
    $manifest = (new CinemaMovieExtension())->manifest();

    expect($manifest->id)->toBe('cinema-movie')
        ->and($manifest->name)->toBe('CinemaMovie')
        ->and($manifest->version)->toBe('1.2.0')
        ->and($manifest->dependencies)->toBe(['files'])
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

it('contributes protected navigation for the movie catalogue', function () {
    $navigation = (new CinemaMovieExtension())->navigation();

    expect($navigation)->toHaveCount(1)
        ->and($navigation[0]['to'])->toBe('/admin/cinema-movie')
        ->and($navigation[0]['permission'])->toBe('cinema-movie.movies.view')
        ->and($navigation[0]['extensionId'])->toBe('cinema-movie');
});
