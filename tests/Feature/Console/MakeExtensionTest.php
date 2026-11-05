<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

it('scaffolds an extension using the SDK v2 surface structure', function (): void {
    $name = 'SdkV2Test' . Str::studly(Str::random(8));
    $id = Str::kebab($name);
    $path = base_path("app/Extensions/{$name}");
    expect(is_dir($path))->toBeFalse();

    try {
        $this->artisan('make:extension', ['name' => $name])
            ->assertSuccessful();

        expect(is_dir($path . '/Public'))->toBeTrue()
            ->and(is_dir($path . '/User'))->toBeTrue()
            ->and(is_dir($path . '/Admin'))->toBeTrue()
            ->and(is_dir($path . '/API'))->toBeTrue()
            ->and(is_file($path . '/API/routes.php'))->toBeTrue()
            ->and(is_file($path . '/resources/js/routes.ts'))->toBeTrue()
            ->and(is_file($path . "/tests/Unit/{$name}ExtensionTest.php"))->toBeTrue()
            ->and(is_file($path . "/tests/Functional/{$name}ApiTest.php"))->toBeTrue()
            ->and(is_file($path . "/tests/E2E/{$name}.spec.ts"))->toBeTrue();

        $functionalTest = file_get_contents($path . "/tests/Functional/{$name}ApiTest.php");
        expect($functionalTest)
            ->toContain('ExtensionApiTestHelper::jsonApiHeaders()')
            ->toContain('/** @var TestCase $testCase */')
            ->toContain('$testCase->actingAs(ExtensionApiTestHelper::createUserWithPermission(')
            ->toContain("new {$name}Extension()")
            ->toContain("'{$id}.items.view'")
            ->not->toContain('actingAsUserWithPermission')
            ->not->toContain('Enable the');

        $manifest = require $path . '/extension.php';

        expect($manifest['surfaces'])->toBe(['admin', 'api']);
        expect(file_get_contents($path . "/{$name}Extension.php"))
            ->toContain('ExtensionNavigationInterface')
            ->toContain('ExtensionRoutesInterface');

        expect(file_get_contents($path . '/resources/js/routes.ts'))
            ->toContain("name: 'admin.{$id}'")
            ->toContain("requiresPermission: '{$id}.items.view'");

        $provider = file_get_contents($path . "/Providers/{$name}ServiceProvider.php");
        expect($provider)
            ->toContain("Pixely's extension migration runner")
            ->not->toContain('loadMigrationsFrom');
    } finally {
        File::deleteDirectory($path);
    }
});

it('rejects unsafe names without writing outside the extension directory', function (): void {
    $probe = 'MakeExtensionBoundary' . Str::random(12);
    $invalidNames = [
        "../{$probe}",
        "../../{$probe}",
        "..\\{$probe}",
        'Invalid--Name',
        '9Invalid',
    ];

    foreach ($invalidNames as $name) {
        $this->artisan('make:extension', ['name' => $name])
            ->expectsOutputToContain('Invalid extension name')
            ->assertFailed();
    }

    expect(is_dir(base_path("app/{$probe}")))->toBeFalse()
        ->and(is_dir(base_path($probe)))->toBeFalse();
});

it('normalizes hyphenated and underscored extension names', function (): void {
    $name = 'Sdk-v2_test-' . Str::random(8);
    $studly = Str::studly($name);
    $path = base_path("app/Extensions/{$studly}");

    expect(is_dir($path))->toBeFalse();

    try {
        $this->artisan('make:extension', ['name' => $name])
            ->assertSuccessful();

        expect(is_file($path . "/{$studly}Extension.php"))->toBeTrue();
    } finally {
        File::deleteDirectory($path);
    }
});
