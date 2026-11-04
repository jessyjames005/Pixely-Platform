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

it('does not invoke the installer or change versions when interactive approval is declined', function (): void {
    ($this->createPackage)('sample-extension.zip', 'sample-extension');
    ($this->trackInstalledVersions)(['sample-extension' => '1.0.0']);
    $this->mock(ExtensionInstallerInterface::class, function (MockInterface $mock): void {
        $mock->shouldNotReceive('update');
    });
    $reportPath = storage_path('framework/testing/extension-update-declined-' . bin2hex(random_bytes(4)) . '.json');

    try {
        $this->artisan('pixely:update', [
            '--source' => $this->packageDirectory,
            '--report' => $reportPath,
        ])
            ->expectsConfirmation('Proceed with the dependency-first extension update plan?', 'no')
            ->expectsOutputToContain('Update cancelled; no extension files were changed.')
            ->assertExitCode(0);

        $report = json_decode(file_get_contents($reportPath), true, flags: JSON_THROW_ON_ERROR);
        expect($report['items'][0]['status'])->toBe('cancelled')
            ->and($report['summary'])->toMatchArray([
                'processed' => 0,
                'updated' => 0,
                'failed' => 0,
                'blocked' => 0,
                'exit_code' => 0,
            ])
            ->and(app(ExtensionVersionRepository::class)->find('sample-extension'))->toBe('1.0.0');
    } finally {
        @unlink($reportPath);
    }
});

it('applies an interactively approved batch in dependency-first order', function (): void {
    ($this->createPackage)(
        'alpha-plugin.zip',
        'alpha-plugin',
        '1.1.0',
        ['zeta-core'],
        ['zeta-core' => '2.0.0'],
    );
    ($this->createPackage)('zeta-core.zip', 'zeta-core', '2.0.0');
    ($this->trackInstalledVersions)(['alpha-plugin' => '1.0.0', 'zeta-core' => '1.0.0']);
    $calls = [];
    $this->mock(ExtensionInstallerInterface::class, function (MockInterface $mock) use (&$calls): void {
        $mock->shouldReceive('update')
            ->twice()
            ->andReturnUsing(function (string $packagePath, string $id) use (&$calls): array {
                $calls[] = $id;
                $version = $id === 'zeta-core' ? '2.0.0' : '1.1.0';
                app(ExtensionVersionRepository::class)->set($id, $version);

                return ['version' => $version];
            });
    });
    $reportPath = storage_path('framework/testing/extension-update-approved-' . bin2hex(random_bytes(4)) . '.json');

    try {
        $this->artisan('pixely:update', [
            '--source' => $this->packageDirectory,
            '--report' => $reportPath,
        ])
            ->expectsConfirmation('Proceed with the dependency-first extension update plan?', 'yes')
            ->expectsOutputToContain('Execution order: zeta-core → alpha-plugin')
            ->assertExitCode(0);

        $report = json_decode(file_get_contents($reportPath), true, flags: JSON_THROW_ON_ERROR);
        expect($calls)->toBe(['zeta-core', 'alpha-plugin'])
            ->and($report['execution_order'])->toBe(['zeta-core', 'alpha-plugin'])
            ->and(array_column($report['items'], 'status'))->toBe(['updated', 'updated'])
            ->and($report['summary'])->toMatchArray(['processed' => 2, 'updated' => 2, 'failed' => 0, 'blocked' => 0]);
    } finally {
        @unlink($reportPath);
    }
});

it('rejects non-interactive apply without explicit approval', function (): void {
    ($this->createPackage)('sample-extension.zip', 'sample-extension');
    ($this->trackInstalledVersions)(['sample-extension' => '1.0.0']);
    $this->mock(ExtensionInstallerInterface::class, function (MockInterface $mock): void {
        $mock->shouldNotReceive('update');
    });
    $reportPath = storage_path('framework/testing/extension-update-no-approval-' . bin2hex(random_bytes(4)) . '.json');

    try {
        $this->artisan('pixely:update', [
            '--source' => $this->packageDirectory,
            '--report' => $reportPath,
            '--no-interaction' => true,
        ])
            ->expectsOutputToContain('Use --yes to explicitly approve updates in a non-interactive environment.')
            ->assertExitCode(1);

        $report = json_decode(file_get_contents($reportPath), true, flags: JSON_THROW_ON_ERROR);
        expect($report['items'][0]['status'])->toBe('approval_required')
            ->and($report['summary'])->toMatchArray(['processed' => 0, 'updated' => 0, 'failed' => 0, 'blocked' => 0])
            ->and(app(ExtensionVersionRepository::class)->find('sample-extension'))->toBe('1.0.0');
    } finally {
        @unlink($reportPath);
    }
});

it('uses --yes to approve a non-interactive update', function (): void {
    ($this->createPackage)('sample-extension.zip', 'sample-extension');
    ($this->trackInstalledVersions)(['sample-extension' => '1.0.0']);
    $this->mock(ExtensionInstallerInterface::class, function (MockInterface $mock): void {
        $mock->shouldReceive('update')
            ->once()
            ->andReturnUsing(function (string $packagePath, string $id): array {
                app(ExtensionVersionRepository::class)->set($id, '1.1.0');

                return ['version' => '1.1.0'];
            });
    });

    $this->artisan('pixely:update', [
        '--source' => $this->packageDirectory,
        '--yes' => true,
        '--no-interaction' => true,
    ])
        ->expectsOutputToContain('Packages processed: 1; updated: 1; failed: 0; blocked: 0.')
        ->assertExitCode(0);

    expect(app(ExtensionVersionRepository::class)->find('sample-extension'))->toBe('1.1.0');
});

it('blocks transitive dependents after a failed update and continues independent candidates', function (): void {
    ($this->createPackage)(
        'alpha-consumer.zip',
        'alpha-consumer',
        '1.1.0',
        ['beta-middle'],
    );
    ($this->createPackage)(
        'beta-middle.zip',
        'beta-middle',
        '1.1.0',
        ['zeta-core'],
    );
    ($this->createPackage)('omega-addon.zip', 'omega-addon');
    ($this->createPackage)('zeta-core.zip', 'zeta-core', '2.0.0');
    ($this->trackInstalledVersions)([
        'alpha-consumer' => '1.0.0',
        'beta-middle' => '1.0.0',
        'omega-addon' => '1.0.0',
        'zeta-core' => '1.0.0',
    ]);
    $calls = [];
    $this->mock(ExtensionInstallerInterface::class, function (MockInterface $mock) use (&$calls): void {
        $mock->shouldReceive('update')
            ->twice()
            ->andReturnUsing(function (string $packagePath, string $id) use (&$calls): array {
                $calls[] = $id;
                if ($id === 'zeta-core') {
                    throw new \RuntimeException('Core update failed.');
                }

                app(ExtensionVersionRepository::class)->set($id, '1.1.0');

                return ['version' => '1.1.0'];
            });
    });
    $reportPath = storage_path('framework/testing/extension-update-partial-' . bin2hex(random_bytes(4)) . '.json');

    try {
        $this->artisan('pixely:update', [
            '--source' => $this->packageDirectory,
            '--report' => $reportPath,
            '--yes' => true,
            '--no-interaction' => true,
        ])
            ->expectsOutputToContain('Execution order: omega-addon → zeta-core → beta-middle → alpha-consumer')
            ->assertExitCode(1);

        $report = json_decode(file_get_contents($reportPath), true, flags: JSON_THROW_ON_ERROR);
        expect($calls)->toBe(['omega-addon', 'zeta-core'])
            ->and($report['execution_order'])->toBe(['omega-addon', 'zeta-core', 'beta-middle', 'alpha-consumer'])
            ->and(array_column($report['items'], 'extension'))->toBe([
                'omega-addon',
                'zeta-core',
                'beta-middle',
                'alpha-consumer',
            ])
            ->and(array_column($report['items'], 'status'))->toBe(['updated', 'failed', 'blocked', 'blocked'])
            ->and($report['summary'])->toMatchArray([
                'processed' => 2,
                'updated' => 1,
                'failed' => 1,
                'blocked' => 2,
                'exit_code' => 1,
            ])
            ->and($report['limitations'][0])->toContain('no global transaction')
            ->and(app(ExtensionVersionRepository::class)->find('omega-addon'))->toBe('1.1.0')
            ->and(app(ExtensionVersionRepository::class)->find('zeta-core'))->toBe('1.0.0')
            ->and(app(ExtensionVersionRepository::class)->find('beta-middle'))->toBe('1.0.0')
            ->and(app(ExtensionVersionRepository::class)->find('alpha-consumer'))->toBe('1.0.0');
    } finally {
        @unlink($reportPath);
    }
});
