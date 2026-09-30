<?php

declare(strict_types=1);

namespace App\Extensions\CinemaMovie;

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
final class CinemaMovieExtension implements ExtensionInterface, ExtensionPermissionsInterface
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
     * @return array<class-string>
     */
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
