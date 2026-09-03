<?php

declare(strict_types=1);

use App\Core\Extensions\Versioning\ExtensionVersionRepository;
use App\Extensions\Gallery\GalleryExtension;
use App\Extensions\Gallery\Models\Photo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('applies the slug and file_size schema step and backfills existing photos', function () {
    // Ensure a clean pre-1.0.2 state for this test's schema assertions
    if (Schema::hasColumn('photos', 'slug')) {
        Schema::table('photos', fn ($table) => $table->dropColumn(['slug', 'file_size']));
    }

    $photo = Photo::create(['title' => 'Sunset', 'filename' => 'gallery/sunset.jpg']);

    $versionRepository = app(ExtensionVersionRepository::class);
    $runner = app(\App\Core\Extensions\Versioning\ExtensionUpgradeRunner::class);

    $versionRepository->set('gallery', '1.0.0');
    $runner->upgrade(new GalleryExtension(), '1.0.2');

    expect(Schema::hasColumn('photos', 'slug'))->toBeTrue();
    expect(Schema::hasColumn('photos', 'file_size'))->toBeTrue();
    expect($photo->refresh()->slug)->not->toBeNull();
    expect($versionRepository->find('gallery'))->toBe('1.0.2');
});

it('applying only up to 1.0.1 does not add the schema columns yet', function () {
    if (Schema::hasColumn('photos', 'slug')) {
        Schema::table('photos', fn ($table) => $table->dropColumn(['slug', 'file_size']));
    }

    $versionRepository = app(ExtensionVersionRepository::class);
    $runner = app(\App\Core\Extensions\Versioning\ExtensionUpgradeRunner::class);

    $versionRepository->set('gallery', '1.0.0');
    $runner->upgrade(new GalleryExtension(), '1.0.1');

    expect(Schema::hasColumn('photos', 'slug'))->toBeFalse();
    expect($versionRepository->find('gallery'))->toBe('1.0.1');
});
