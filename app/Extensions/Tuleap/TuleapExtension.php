<?php

declare(strict_types=1);

namespace App\Extensions\Tuleap;

use App\Core\Extensions\Capabilities\Contracts\ExtensionNavigationInterface;
use App\Core\Extensions\Capabilities\Contracts\ExtensionRoutesInterface;
use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Manifest\ExtensionManifest;
use App\Core\Extensions\Permissions\ExtensionPermissionsInterface;
use App\Core\Extensions\Translations\ExtensionTranslatableInterface;
use App\Extensions\Tuleap\Providers\TuleapServiceProvider;

/**
 * Tuleap Dashboard extension: sprint management dashboard for Tuleap.
 *
 * Provides views for sprint planning, review, analytics, team management,
 * and retrospective actions. Stores local sprint configuration in Laravel
 * database and proxies Tuleap API calls server-side.
 */
final class TuleapExtension implements ExtensionInterface, ExtensionNavigationInterface, ExtensionRoutesInterface, ExtensionPermissionsInterface, ExtensionTranslatableInterface
{
    public function manifest(): ExtensionManifest
    {
        return new ExtensionManifest(
            id: 'tuleap',
            name: 'Tuleap',
            version: '1.0.0',
            minimum_kernel_version: '1.0.0',
            class: self::class,
            path: 'app/Extensions/Tuleap',
            dependencies: [],
            surfaces: ['admin', 'api'],
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function navigation(): array
    {
        return [[
            'id' => 'tuleap',
            'label' => 'Tuleap',
            'to' => '/admin/tuleap/dashboard',
            'icon' => 'mdi-chart-timeline-variant',
            'permission' => 'tuleap.dashboard.view',
            'surface' => 'admin',
            'order' => 40,
            'extensionId' => 'tuleap',
            'children' => [
                ['label' => 'Dashboard', 'to' => '/admin/tuleap/dashboard', 'icon' => 'mdi-view-dashboard-outline'],
                ['label' => 'Sprint Planning', 'to' => '/admin/tuleap/planning', 'icon' => 'mdi-calendar-check-outline', 'permission' => 'tuleap.sprint.manage'],
                ['label' => 'Sprint Review', 'to' => '/admin/tuleap/review', 'icon' => 'mdi-clipboard-check-outline'],
                ['label' => 'Retrospective', 'to' => '/admin/tuleap/retrospective', 'icon' => 'mdi-refresh', 'permission' => 'tuleap.retro.manage'],
                ['label' => 'Trends', 'to' => '/admin/tuleap/tendances', 'icon' => 'mdi-chart-line'],
                ['label' => 'Team', 'to' => '/admin/tuleap/equipe', 'icon' => 'mdi-account-group-outline', 'permission' => 'tuleap.team.manage'],
                ['label' => 'System', 'to' => '/admin/tuleap/system', 'icon' => 'mdi-cog-outline', 'permission' => 'tuleap.config.manage'],
            ],
        ]];
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

    /**
     * @return list<array{file:string}>
     */
    public function routes(): array
    {
        return [['file' => 'app/Extensions/Tuleap/API/routes.php']];
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

    public function translationsPath(): string
    {
        return base_path('app/Extensions/Tuleap/lang');
    }
}
