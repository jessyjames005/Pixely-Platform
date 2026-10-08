<?php

declare(strict_types=1);

namespace App\Extensions\CinemaMovie;

use App\Core\Extensions\Capabilities\Contracts\ExtensionNavigationInterface;
use App\Core\Extensions\Capabilities\Contracts\ExtensionRoutesInterface;
use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Manifest\ExtensionManifest;
use App\Core\Extensions\Permissions\ExtensionPermissionsInterface;
use App\Extensions\CinemaMovie\Providers\CinemaMovieServiceProvider;

/**
 * CinemaMovie extension.
 *
 * Add ExtensionUpgradableInterface (see App\Core\Extensions\Versioning)
 * once this extension ships its first versioned upgrade step, and
 * ExtensionTranslatableInterface (see App\Core\Extensions\Translations)
 * once its lang/ files are ready to be browsable in the Translations
 * extension screen.
 */
final class CinemaMovieExtension implements ExtensionInterface, ExtensionNavigationInterface, ExtensionRoutesInterface, ExtensionPermissionsInterface
{
    public function manifest(): ExtensionManifest
    {
        return new ExtensionManifest(
            id: 'cinema-movie',
            name: 'CinemaMovie',
            version: '1.0.0',
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
            'cinema-movie.items.view',
            'cinema-movie.items.manage',
            'cinema-movie.items.delete',
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
            'permission' => 'cinema-movie.items.view',
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
}
