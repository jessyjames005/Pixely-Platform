<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

it('scaffolds an extension using the SDK v2 surface structure', function (): void {
    $id = 'sdk-v2-test';
    $path = base_path('app/Extensions/SdkV2Test');

    File::deleteDirectory($path);

    try {
        $this->artisan('make:extension', ['name' => 'SdkV2Test'])
            ->assertSuccessful();

        expect(is_dir($path . '/Public'))->toBeTrue()
            ->and(is_dir($path . '/User'))->toBeTrue()
            ->and(is_dir($path . '/Admin'))->toBeTrue()
            ->and(is_dir($path . '/API'))->toBeTrue()
            ->and(is_file($path . '/API/routes.php'))->toBeTrue();

        $manifest = require $path . '/extension.php';

        expect($manifest['surfaces'])->toBe(['admin', 'api']);
        expect(file_get_contents($path . '/SdkV2TestExtension.php'))
            ->toContain('ExtensionNavigationInterface')
            ->toContain('ExtensionRoutesInterface');
    } finally {
        File::deleteDirectory($path);
    }
});
