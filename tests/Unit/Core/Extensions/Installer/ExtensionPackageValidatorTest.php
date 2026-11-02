<?php

declare(strict_types=1);

use App\Core\Extensions\Installer\ExtensionPackageValidator;
use Tests\Fixtures\Extensions\FakeExtensionPackageBuilder;

beforeEach(function () {
    $this->validator = new ExtensionPackageValidator();
    $this->stagingDir = sys_get_temp_dir() . '/pixely-staging-' . uniqid('', true);
    mkdir($this->stagingDir, 0777, true);
});

afterEach(function () {
    if (is_dir($this->stagingDir)) {
        (new Illuminate\Filesystem\Filesystem())->deleteDirectory($this->stagingDir);
    }
});

it('extracts a valid zip successfully', function () {
    $zipPath = FakeExtensionPackageBuilder::validPackage();

    $this->validator->extractSafely($zipPath, $this->stagingDir);

    expect(file_exists($this->stagingDir . '/extension.php'))->toBeTrue();
});

it('rejects a zip containing a path traversal entry', function () {
    $zipPath = FakeExtensionPackageBuilder::zipSlipPackage();

    $this->validator->extractSafely($zipPath, $this->stagingDir);
})->throws(RuntimeException::class, 'Unsafe path detected');

it('locates and validates a well-formed manifest', function () {
    $zipPath = FakeExtensionPackageBuilder::validPackage('demo', '2.1.0');
    $this->validator->extractSafely($zipPath, $this->stagingDir);

    $manifest = $this->validator->locateAndValidateManifest($this->stagingDir);

    expect($manifest['id'])->toBe('demo')
        ->and($manifest['version'])->toBe('2.1.0')
        ->and($manifest['minimum_kernel_version'])->toBe('1.0.0');
});

it('preserves a declared minimum kernel version in the validated manifest', function () {
    file_put_contents($this->stagingDir . '/extension.php', '<?php return ' . var_export([
        'id' => 'demo',
        'name' => 'Demo Extension',
        'version' => '1.0.0',
        'minimum_kernel_version' => '2.0.0',
        'class' => 'App\\Extensions\\Demo\\DemoExtension',
    ], true) . ';');

    $manifest = $this->validator->locateAndValidateManifest($this->stagingDir);

    expect($manifest['minimum_kernel_version'])->toBe('2.0.0');
});

it('rejects a manifest with an invalid minimum kernel version', function () {
    file_put_contents($this->stagingDir . '/extension.php', '<?php return ' . var_export([
        'id' => 'demo',
        'name' => 'Demo Extension',
        'version' => '1.0.0',
        'minimum_kernel_version' => 'invalid',
        'class' => 'App\\Extensions\\Demo\\DemoExtension',
    ], true) . ';');

    $this->validator->locateAndValidateManifest($this->stagingDir);
})->throws(RuntimeException::class, 'minimum_kernel_version');

it('rejects a package with no manifest', function () {
    $zipPath = FakeExtensionPackageBuilder::missingManifestPackage();
    $this->validator->extractSafely($zipPath, $this->stagingDir);

    $this->validator->locateAndValidateManifest($this->stagingDir);
})->throws(RuntimeException::class, 'No extension.php manifest found');

it('rejects a manifest with an invalid version format', function () {
    $zipPath = FakeExtensionPackageBuilder::invalidVersionPackage();
    $this->validator->extractSafely($zipPath, $this->stagingDir);

    $this->validator->locateAndValidateManifest($this->stagingDir);
})->throws(RuntimeException::class, 'semantic versioning');
