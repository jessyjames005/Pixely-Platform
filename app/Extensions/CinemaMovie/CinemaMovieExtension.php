<?php

declare(strict_types=1);

namespace App\Extensions\CinemaMovie;

use App\Core\Extensions\Capabilities\Contracts\ExtensionNavigationInterface;
use App\Core\Extensions\Capabilities\Contracts\ExtensionRoutesInterface;
use App\Core\Extensions\Capabilities\Contracts\ExtensionSettingsInterface;
use App\Core\Extensions\Configuration\ExtensionConfigurableInterface;
use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Manifest\ExtensionManifest;
use App\Core\Extensions\Permissions\ExtensionPermissionsInterface;
use App\Core\Extensions\Translations\ExtensionTranslatableInterface;
use App\Core\Extensions\Versioning\ExtensionUpgradableInterface;
use App\Extensions\CinemaMovie\Providers\CinemaMovieServiceProvider;
use App\Extensions\CinemaMovie\Upgrades\RenameCinemaMoviePermissionsStep;

/**
 * CinemaMovie extension.
 *
 * Implements ExtensionTranslatableInterface so the Translations extension
 * can discover its UI catalog, and ExtensionUpgradableInterface for
 * data-preserving package updates.
 */
final class CinemaMovieExtension implements
    ExtensionInterface,
    ExtensionNavigationInterface,
    ExtensionRoutesInterface,
    ExtensionPermissionsInterface,
    ExtensionSettingsInterface,
    ExtensionConfigurableInterface,
    ExtensionTranslatableInterface,
    ExtensionUpgradableInterface
{
    public function manifest(): ExtensionManifest
    {
        return new ExtensionManifest(
            id: 'cinema-movie',
            name: 'CinemaMovie',
            version: '1.1.0',
            minimum_kernel_version: '1.0.0',
            class: self::class,
            path: 'app/Extensions/CinemaMovie',
            dependencies: [],
            surfaces: ['admin', 'api'],
        );
    }

    /**
     * Declared permissions, following the platform convention:
     * <domain>.<object>.<view|manage|delete>. Synced automatically
     * on install/update/enable — see ExtensionPermissionSynchronizer.
     *
     * @return array<int, string>
     */
    public function declaredPermissions(): array
    {
        return [
            'cinema-movie.movies.view',
            'cinema-movie.movies.manage',
            'cinema-movie.movies.delete',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function navigation(): array
    {
        return [[
            'id' => 'cinema-movie',
            'label' => 'CinemaMovie',
            'to' => '/admin/cinema-movie',
            'icon' => 'mdi-movie-open-outline',
            'permission' => 'cinema-movie.movies.view',
            'surface' => 'admin',
            'order' => 50,
            'extensionId' => 'cinema-movie',
        ]];
    }

    /**
     * @return list<array{file:string}>
     */
    public function routes(): array
    {
        return [['file' => 'app/Extensions/CinemaMovie/API/routes.php']];
    }

    public function providers(): array
    {
        return [
            CinemaMovieServiceProvider::class,
        ];
    }

    public function boot(): void
    {
        // Nothing to boot.
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function settings(): array
    {
        return [
            'supported_locales' => [
                'type' => 'string[]',
                'label' => 'Supported content locales',
                'default' => ['en', 'fr'],
            ],
        ];
    }

    public function defaultConfiguration(): array
    {
        return array_map(
            static fn (array $setting): mixed => $setting['default'],
            $this->settings(),
        );
    }

    /**
     * @return list<\App\Core\Extensions\Versioning\ExtensionUpgradeStepInterface>
     */
    public function upgradeSteps(): array
    {
        return [new RenameCinemaMoviePermissionsStep()];
    }

    public function translationsPath(): string
    {
        return base_path('app/Extensions/CinemaMovie/lang');
    }
}
