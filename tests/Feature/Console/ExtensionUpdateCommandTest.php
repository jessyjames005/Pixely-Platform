<?php

declare(strict_types=1);

use App\Core\Extensions\Installer\ExtensionInstallerInterface;
use App\Core\Extensions\Versioning\ExtensionVersionRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery\MockInterface;

beforeEach(function (): void {
    if (! class_exists(ZipArchive::class)) {
        $this->markTestSkipped('The PHP zip extension is required for extension package tests.');
    }

    $this->packageDirectory = storage_path('framework/testing/extension-updates-' . bin2hex(random_bytes(4)));
    File::ensureDirectoryExists($this->packageDirectory);
});

afterEach(function (): void {
    File::deleteDirectory($this->packageDirectory);
});

it('reports an empty local update directory without changing extensions', function (): void {
    $this->artisan('pixely:update', ['--source' => $this->packageDirectory . '/missing'])
        ->expectsOutputToContain('No update directory found')
        ->assertExitCode(0);
});

it('shows a validated update plan in dry-run mode without invoking the installer', function (): void {
    $manifest = <<<'MANIFEST'
<?php
return [
    'id' => 'sample-extension',
    'name' => 'Sample Extension',
    'version' => '1.1.0',
    'class' => 'App\\Extensions\\SampleExtension\\SampleExtension',
    'requires' => [],
];
MANIFEST;
    $archivePath = $this->packageDirectory . '/sample-extension.zip';
    $archive = new ZipArchive();
    expect($archive->open($archivePath, ZipArchive::CREATE))->toBeTrue();
    $archive->addFromString('extension.php', $manifest);
    $archive->close();

    // ExtensionVersionRepository is intentionally final; use its real implementation
    // with a minimal schema instead of attempting to mock the class.
    Schema::create('extension_installed_versions', function (Blueprint $table): void {
        $table->id();
        $table->string('extension_id')->unique();
        $table->string('version');
        $table->timestamps();
    });
    app(ExtensionVersionRepository::class)->set('sample-extension', '1.0.0');
    $this->mock(ExtensionInstallerInterface::class, function (MockInterface $mock): void {
        $mock->shouldNotReceive('update');
    });

    $this->artisan('pixely:update', ['--source' => $this->packageDirectory, '--dry-run' => true])
        ->expectsOutputToContain('sample-extension (1.0.0 → 1.1.0)')
        ->expectsOutputToContain('Packages processed: 1; failed: 0.')
        ->assertExitCode(0);
});
