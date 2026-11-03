<?php

declare(strict_types=1);

use App\Core\Extensions\Enum\ExtensionStatus;
use App\Core\Extensions\Migration\ExtensionStateJsonImporter;
use App\Core\Extensions\Repositories\DatabaseExtensionStateRepository;
use App\Core\Extensions\State\ExtensionState;
use App\Extensions\Gallery\GalleryExtension;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('persists and retrieves extension runtime state from the database', function () {
    $repository = new DatabaseExtensionStateRepository();
    $extension = new GalleryExtension();

    $repository->save(new ExtensionState($extension, ExtensionStatus::Disabled));

    expect($repository->find('gallery')?->status)->toBe(ExtensionStatus::Disabled)
        ->and($repository->all()['gallery']->extension->manifest()->id)->toBe('gallery');

    $repository->update(new ExtensionState($extension, ExtensionStatus::Enabled));

    expect($repository->find('gallery')?->status)->toBe(ExtensionStatus::Enabled);
});

it('imports legacy JSON once and never overwrites an existing database state', function () {
    $path = tempnam(sys_get_temp_dir(), 'pixely-extension-state-');
    file_put_contents($path, json_encode([
        'gallery' => [
            'id' => 'gallery',
            'class' => GalleryExtension::class,
            'status' => 'disabled',
        ],
    ], JSON_THROW_ON_ERROR));

    try {
        $importer = new ExtensionStateJsonImporter();

        expect($importer->import($path)['imported'])->toBe(1);
        expect(DB::table('extension_states')->where('extension_id', 'gallery')->value('status'))->toBe('disabled');

        DB::table('extension_states')->where('extension_id', 'gallery')->update(['status' => 'enabled']);
        expect($importer->import($path)['imported'])->toBe(0);
        expect(DB::table('extension_states')->where('extension_id', 'gallery')->value('status'))->toBe('enabled');
    } finally {
        @unlink($path);
    }
});
