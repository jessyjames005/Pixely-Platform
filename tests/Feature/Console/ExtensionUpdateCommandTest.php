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
    $this->createPackage = function (
        string $filename,
        string $id,
        string $version = '1.1.0',
        array $requires = [],
        array $requiredVersions = [],
    ): void {
        $class = str_replace('-', '', ucwords($id, '-'));
        $manifest = [
            'id' => $id,
            'name' => ucfirst($id),
            'version' => $version,
            'class' => "App\\Extensions\\{$class}\\{$class}",
            'requires' => $requires,
            'require_versions' => $requiredVersions,
        ];
        $archive = new ZipArchive();
        $archivePath = $this->packageDirectory . '/' . $filename;
        expect($archive->open($archivePath, ZipArchive::CREATE))->toBeTrue();
        $archive->addFromString('extension.php', "<?php\nreturn " . var_export($manifest, true) . ";\n");
        $archive->close();
    };
    $this->trackInstalledVersions = function (array $versions): void {
        Schema::create('extension_installed_versions', function (Blueprint $table): void {
            $table->id();
            $table->string('extension_id')->unique();
            $table->string('version');
            $table->timestamps();
        });

        foreach ($versions as $id => $version) {
            app(ExtensionVersionRepository::class)->set($id, $version);
        }
    };
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
    ($this->createPackage)('sample-extension.zip', 'sample-extension');
    ($this->trackInstalledVersions)(['sample-extension' => '1.0.0']);
    $this->mock(ExtensionInstallerInterface::class, function (MockInterface $mock): void {
        $mock->shouldNotReceive('update');
    });

    $this->artisan('pixely:update', ['--source' => $this->packageDirectory, '--dry-run' => true])
        ->expectsOutputToContain('sample-extension (1.0.0 → 1.1.0)')
        ->expectsOutputToContain('Packages processed: 1; failed: 0.')
        ->assertExitCode(0);
});

it('plans all selected dry-run candidates in global dependency-first order', function (): void {
    ($this->createPackage)(
        'alpha-plugin.zip',
        'alpha-plugin',
        '1.1.0',
        ['zeta-core'],
        ['zeta-core' => '2.0.0'],
    );
    ($this->createPackage)('zeta-core.zip', 'zeta-core', '2.0.0');
    ($this->trackInstalledVersions)(['alpha-plugin' => '1.0.0', 'zeta-core' => '1.0.0']);
    $this->mock(ExtensionInstallerInterface::class, function (MockInterface $mock): void {
        $mock->shouldNotReceive('update');
    });
    $reportPath = storage_path('framework/testing/extension-update-order-' . bin2hex(random_bytes(4)) . '.json');

    try {
        $this->artisan('pixely:update', [
            '--source' => $this->packageDirectory,
            '--dry-run' => true,
            '--report' => $reportPath,
        ])
            ->expectsOutputToContain('Execution order: zeta-core → alpha-plugin')
            ->expectsOutputToContain('zeta-core (1.0.0 → 2.0.0)')
            ->expectsOutputToContain('alpha-plugin (1.0.0 → 1.1.0)')
            ->expectsOutputToContain('Packages processed: 2; failed: 0.')
            ->assertExitCode(0);

        $report = json_decode(file_get_contents($reportPath), true, flags: JSON_THROW_ON_ERROR);
        expect($report['execution_order'])->toBe(['zeta-core', 'alpha-plugin'])
            ->and(array_column($report['items'], 'extension'))->toBe(['zeta-core', 'alpha-plugin'])
            ->and($report['summary'])->toMatchArray(['processed' => 2, 'failed' => 0]);

        expect(app(ExtensionVersionRepository::class)->find('alpha-plugin'))->toBe('1.0.0')
            ->and(app(ExtensionVersionRepository::class)->find('zeta-core'))->toBe('1.0.0');
    } finally {
        @unlink($reportPath);
    }
});

it('reports a blocked global dry-run plan without invoking the installer', function (): void {
    ($this->createPackage)(
        'alpha-plugin.zip',
        'alpha-plugin',
        '1.1.0',
        ['zeta-core'],
        ['zeta-core' => '2.0.0'],
    );
    ($this->createPackage)('zeta-core.zip', 'zeta-core', '1.5.0');
    ($this->trackInstalledVersions)(['alpha-plugin' => '1.0.0', 'zeta-core' => '1.0.0']);
    $this->mock(ExtensionInstallerInterface::class, function (MockInterface $mock): void {
        $mock->shouldNotReceive('update');
    });
    $reportPath = storage_path('framework/testing/extension-update-blocked-' . bin2hex(random_bytes(4)) . '.json');

    try {
        $this->artisan('pixely:update', [
            '--source' => $this->packageDirectory,
            '--dry-run' => true,
            '--report' => $reportPath,
        ])
            ->expectsOutputToContain('Batch update plan blocked:')
            ->expectsOutputToContain('Packages processed: 0; failed: 2.')
            ->assertExitCode(1);

        $report = json_decode(file_get_contents($reportPath), true, flags: JSON_THROW_ON_ERROR);
        expect($report['execution_order'])->toBe([])
            ->and($report['summary'])->toMatchArray(['processed' => 0, 'failed' => 2])
            ->and(array_column($report['items'], 'status'))->toBe(['failed', 'failed'])
            ->and($report['items'][0]['message'])->toContain('requires [zeta-core] version [2.0.0] or newer');

        expect(app(ExtensionVersionRepository::class)->find('alpha-plugin'))->toBe('1.0.0')
            ->and(app(ExtensionVersionRepository::class)->find('zeta-core'))->toBe('1.0.0');
    } finally {
        @unlink($reportPath);
    }
});

it('honors the extension filter when building a dry-run batch', function (): void {
    ($this->createPackage)('alpha-plugin.zip', 'alpha-plugin');
    ($this->createPackage)('zeta-core.zip', 'zeta-core', '2.0.0');
    ($this->trackInstalledVersions)(['alpha-plugin' => '1.0.0', 'zeta-core' => '1.0.0']);
    $this->mock(ExtensionInstallerInterface::class, function (MockInterface $mock): void {
        $mock->shouldNotReceive('update');
    });
    $reportPath = storage_path('framework/testing/extension-update-filter-' . bin2hex(random_bytes(4)) . '.json');

    try {
        $this->artisan('pixely:update', [
            '--source' => $this->packageDirectory,
            '--extension' => 'alpha-plugin',
            '--dry-run' => true,
            '--report' => $reportPath,
        ])
            ->expectsOutputToContain('Execution order: alpha-plugin')
            ->expectsOutputToContain('Packages processed: 1; failed: 0.')
            ->assertExitCode(0);

        $report = json_decode(file_get_contents($reportPath), true, flags: JSON_THROW_ON_ERROR);
        expect($report['filter'])->toBe('alpha-plugin')
            ->and($report['execution_order'])->toBe(['alpha-plugin'])
            ->and(array_column($report['items'], 'extension'))->toBe(['alpha-plugin']);
    } finally {
        @unlink($reportPath);
    }
});
