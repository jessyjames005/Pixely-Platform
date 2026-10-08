<?php

declare(strict_types=1);

use App\Core\Extensions\Capabilities\Contracts\ExtensionNavigationInterface;
use App\Core\Extensions\Capabilities\Contracts\ExtensionRoutesInterface;
use App\Extensions\CinemaMovie\CinemaMovieExtension;
use App\Extensions\Files\FilesExtension;
use App\Extensions\Gallery\GalleryExtension;
use App\Extensions\Translations\TranslationsExtension;
use App\Extensions\Tuleap\TuleapExtension;

it('adapts every existing extension to the SDK v2 navigation and route contracts', function (string $class): void {
    $extension = new $class();

    expect($extension)->toBeInstanceOf(ExtensionNavigationInterface::class)
        ->and($extension)->toBeInstanceOf(ExtensionRoutesInterface::class)
        ->and($extension->routes())->not->toBeEmpty();
})->with([
    GalleryExtension::class,
    FilesExtension::class,
    TranslationsExtension::class,
    TuleapExtension::class,
    CinemaMovieExtension::class,
]);

it('declares surface folders through the extension manifest', function (string $class): void {
    $surfaces = (new $class())->manifest()->surfaces;

    expect($surfaces)->toContain('admin', 'api');
})->with([
    GalleryExtension::class,
    FilesExtension::class,
    TranslationsExtension::class,
    TuleapExtension::class,
    CinemaMovieExtension::class,
]);
