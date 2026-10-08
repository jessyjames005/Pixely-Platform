<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Extensions\Capabilities;

use App\Core\Extensions\Capabilities\Contracts\ExtensionBlocksInterface;
use App\Core\Extensions\Capabilities\Contracts\ExtensionNavigationInterface;
use App\Core\Extensions\Capabilities\Contracts\ExtensionRoutesInterface;
use App\Core\Extensions\Capabilities\Contracts\ExtensionSettingsInterface;
use App\Core\Extensions\Capabilities\Enum\ExtensionCapability;
use App\Core\Extensions\Capabilities\Registry\ExtensionCapabilityRegistry;
use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Manifest\ExtensionManifest;
use App\Core\Extensions\Permissions\ExtensionPermissionsInterface;
use PHPUnit\Framework\TestCase;

final class ExtensionCapabilityRegistryTest extends TestCase
{
    public function test_it_detects_all_supported_capabilities(): void
    {
        $extension = new class implements
            ExtensionInterface,
            ExtensionNavigationInterface,
            ExtensionRoutesInterface,
            ExtensionBlocksInterface,
            ExtensionSettingsInterface,
            ExtensionPermissionsInterface
        {
            public function manifest(): ExtensionManifest
            {
                return new ExtensionManifest(
                    'demo',
                    'Demo',
                    '1.0.0',
                    self::class,
                    'app/Extensions/Demo',
                );
            }

            public function providers(): array { return []; }

            public function boot(): void {}

            public function navigation(): array { return []; }

            public function routes(): array { return []; }

            public function blocks(): array { return []; }

            public function settings(): array { return []; }

            public function declaredPermissions(): array { return []; }
        };

        $registry = new ExtensionCapabilityRegistry();

        self::assertSame(ExtensionCapability::cases(), $registry->for($extension));
    }

    public function test_it_only_reports_capabilities_that_are_declared(): void
    {
        $extension = new class implements ExtensionInterface, ExtensionSettingsInterface
        {
            public function manifest(): ExtensionManifest
            {
                return new ExtensionManifest(
                    'demo',
                    'Demo',
                    '1.0.0',
                    self::class,
                    'app/Extensions/Demo',
                );
            }

            public function providers(): array { return []; }

            public function boot(): void {}

            public function settings(): array { return []; }
        };

        $registry = new ExtensionCapabilityRegistry();

        self::assertSame([ExtensionCapability::Settings], $registry->for($extension));
    }
}
