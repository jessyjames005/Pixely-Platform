<?php

declare(strict_types=1);

use App\Core\Extensions\Audit\ExtensionAuditLogger;
use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Manifest\ExtensionManifest;
use App\Core\Extensions\Versioning\ExtensionUpgradableInterface;
use App\Core\Extensions\Versioning\ExtensionUpgradeRunner;
use App\Core\Extensions\Versioning\ExtensionUpgradeStepInterface;
use App\Core\Extensions\Versioning\ExtensionVersionRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// A no-op step for testing
final class FakeStep implements ExtensionUpgradeStepInterface
{
    public function __construct(
        private readonly string $version,
        private readonly \Closure $onApply,
    ) {
    }

    public function version(): string
    {
        return $this->version;
    }

    public function description(): string
    {
        return "Fake step to {$this->version}";
    }

    public function apply(): void
    {
        ($this->onApply)();
    }
}

final class FakeUpgradableExtension implements ExtensionInterface, ExtensionUpgradableInterface
{
    /** @param ExtensionUpgradeStepInterface[] $steps */
    public function __construct(
        private readonly array $steps,
        private readonly string $id = 'fake-upgradable',
    ) {
    }

    public function manifest(): ExtensionManifest
    {
        return new ExtensionManifest(
            id: $this->id,
            name: 'Fake',
            version: '1.0.0',
            class: self::class,
            path: 'tests/Fakes',
            dependencies: [],
        );
    }

    public function upgradeSteps(): array
    {
        return $this->steps;
    }

    public function providers(): array { return []; }
    public function boot(): void {}
}

final class FakeNonUpgradableExtension implements ExtensionInterface
{
    public function manifest(): ExtensionManifest
    {
        return new ExtensionManifest(
            id: 'fake-non-upgradable',
            name: 'Fake',
            version: '1.0.0',
            class: self::class,
            path: 'tests/Fakes',
            dependencies: [],
        );
    }

    public function providers(): array { return []; }
    public function boot(): void {}
}

beforeEach(function () {
    $this->versionRepository = new ExtensionVersionRepository();
    $this->runner = new ExtensionUpgradeRunner($this->versionRepository, app(ExtensionAuditLogger::class));
});

it('records the version directly on a fresh install, without running any step', function () {
    $this->runner->recordFreshInstall('demo', '2.5.0');

    expect($this->versionRepository->find('demo'))->toBe('2.5.0');
});

it('applies all pending steps in ascending version order', function () {
    $applied = [];

    $extension = new FakeUpgradableExtension([
        new FakeStep('1.0.2', function () use (&$applied) { $applied[] = '1.0.2'; }),
        new FakeStep('1.0.1', function () use (&$applied) { $applied[] = '1.0.1'; }),
    ]);

    $this->runner->recordFreshInstall($extension->manifest()->id, '1.0.0');
    $this->runner->upgrade($extension, '1.0.2');

    expect($applied)->toBe(['1.0.1', '1.0.2']);
    expect($this->versionRepository->find($extension->manifest()->id))->toBe('1.0.2');
});

it('only applies steps strictly after the installed version', function () {
    $applied = [];

    $extension = new FakeUpgradableExtension([
        new FakeStep('1.0.1', function () use (&$applied) { $applied[] = '1.0.1'; }),
        new FakeStep('1.0.2', function () use (&$applied) { $applied[] = '1.0.2'; }),
    ]);

    // Already at 1.0.1 — only 1.0.2 should run
    $this->runner->recordFreshInstall($extension->manifest()->id, '1.0.1');
    $this->runner->upgrade($extension, '1.0.2');

    expect($applied)->toBe(['1.0.2']);
});

it('does not apply steps beyond the target version', function () {
    $applied = [];

    $extension = new FakeUpgradableExtension([
        new FakeStep('1.0.1', function () use (&$applied) { $applied[] = '1.0.1'; }),
        new FakeStep('2.0.0', function () use (&$applied) { $applied[] = '2.0.0'; }),
    ]);

    $this->runner->recordFreshInstall($extension->manifest()->id, '1.0.0');
    $this->runner->upgrade($extension, '1.0.1');

    expect($applied)->toBe(['1.0.1']);
    expect($this->versionRepository->find($extension->manifest()->id))->toBe('1.0.1');
});

it('keeps earlier successful steps when a later step fails', function () {
    $applied = [];

    $extension = new FakeUpgradableExtension([
        new FakeStep('1.0.1', function () use (&$applied) { $applied[] = '1.0.1'; }),
        new FakeStep('1.0.2', function () {
            throw new RuntimeException('boom');
        }),
        new FakeStep('1.0.3', function () use (&$applied) { $applied[] = '1.0.3'; }),
    ]);

    $this->runner->recordFreshInstall($extension->manifest()->id, '1.0.0');

    expect(fn () => $this->runner->upgrade($extension, '1.0.3'))
        ->toThrow(RuntimeException::class);

    // Step 1.0.1 succeeded and stuck; 1.0.3 never ran because 1.0.2 failed first
    expect($applied)->toBe(['1.0.1']);
    expect($this->versionRepository->find($extension->manifest()->id))->toBe('1.0.1');
});

it('advances the version marker for a non-upgradable extension without running any step', function () {
    $extension = new FakeNonUpgradableExtension();

    $this->runner->recordFreshInstall($extension->manifest()->id, '1.0.0');
    $this->runner->upgrade($extension, '1.5.0');

    expect($this->versionRepository->find($extension->manifest()->id))->toBe('1.5.0');
});

it('treats a never-tracked extension as installed at version 0.0.0', function () {
    $applied = [];

    $extension = new FakeUpgradableExtension([
        new FakeStep('1.0.0', function () use (&$applied) { $applied[] = '1.0.0'; }),
    ]);

    // No recordFreshInstall call — simulates an extension that
    // existed before this versioning mechanism was introduced.
    $this->runner->upgrade($extension, '1.0.0');

    expect($applied)->toBe(['1.0.0']);
});
