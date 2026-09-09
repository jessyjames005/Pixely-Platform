<?php

declare(strict_types=1);

namespace App\Extensions\Tuleap;

use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Manifest\ExtensionManifest;
use App\Core\Extensions\Permissions\ExtensionPermissionsInterface;
use App\Extensions\Tuleap\Providers\TuleapServiceProvider;

/**
 * Tuleap Dashboard extension: sprint management dashboard for Tuleap.
 *
 * Provides views for sprint planning, review, analytics, team management,
 * and retrospective actions. Stores local sprint configuration in Laravel
 * database and proxies Tuleap API calls server-side.
 */
final class TuleapExtension implements ExtensionInterface, ExtensionPermissionsInterface
{
    public function manifest(): ExtensionManifest
    {
        return new ExtensionManifest(
            id: 'tuleap',
            name: 'Tuleap',
            version: '1.0.0',
            class: self::class,
            path: 'app/Extensions/Tuleap',
            dependencies: [],
        );
    }

    /**
     * @return array<int, string>
     */
    public function declaredPermissions(): array
    {
        return [
            'tuleap.dashboard.view',
            'tuleap.dashboard.manage',
            'tuleap.team.manage',
            'tuleap.sprint.manage',
            'tuleap.retro.manage',
            'tuleap.config.manage',
        ];
    }

    public function providers(): array
    {
        return [
            TuleapServiceProvider::class,
        ];
    }

    public function boot(): void
    {
        // Nothing to boot.
    }
}