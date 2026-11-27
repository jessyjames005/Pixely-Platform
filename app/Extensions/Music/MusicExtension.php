<?php

declare(strict_types=1);

namespace App\Extensions\Music;

use App\Core\Extensions\Capabilities\Contracts\ExtensionNavigationInterface;
use App\Core\Extensions\Capabilities\Contracts\ExtensionRoutesInterface;
use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Manifest\ExtensionManifest;
use App\Core\Extensions\Permissions\ExtensionPermissionsInterface;
use App\Extensions\Music\Providers\MusicServiceProvider;

/**
 * Music extension.
 *
 * Add ExtensionUpgradableInterface (see App\Core\Extensions\Versioning)
 * once this extension ships its first versioned upgrade step, and
 * ExtensionTranslatableInterface (see App\Core\Extensions\Translations)
 * once its lang/ files are ready to be browsable in the Translations
 * extension screen.
 */
final class MusicExtension implements
    ExtensionInterface,
    ExtensionNavigationInterface,
    ExtensionRoutesInterface,
    ExtensionPermissionsInterface
{
    public function manifest(): ExtensionManifest
    {
        return new ExtensionManifest(
            id: 'music',
            name: 'Music',
            version: '1.0.0',
            minimum_kernel_version: '1.0.0',
            class: self::class,
            path: 'app/Extensions/Music',
            dependencies: ['files'],
            surfaces: ['admin', 'api', 'public'],
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function navigation(): array
    {
        return [[
            'id' => 'music',
            'label' => 'Music',
            'to' => '/admin/music',
            'icon' => 'mdi-puzzle-outline',
            'permission' => 'music.tracks.view',
            'surface' => 'admin',
            'order' => 100,
            'extensionId' => 'music',
        ]];
    }

    /**
     * @return list<array{file:string}>
     */
    public function routes(): array
    {
        return [['file' => 'app/Extensions/Music/API/routes.php']];
    }

    /**
     * Declared permissions, following the platform convention:
     * <domain>.<object>.<view|manage|delete>. Synced automatically
     * on install/update/enable — see ExtensionPermissionSynchronizer.
     *
     * Each catalogue resource (tracks, albums, artists, genres) exposes the
     * three standard actions so the administration screen can gate read,
     * write and delete independently.
     *
     * @return array<int, string>
     */
    public function declaredPermissions(): array
    {
        return [
            'music.tracks.view',
            'music.tracks.manage',
            'music.tracks.delete',
            'music.albums.view',
            'music.albums.manage',
            'music.albums.delete',
            'music.artists.view',
            'music.artists.manage',
            'music.artists.delete',
            'music.genres.view',
            'music.genres.manage',
            'music.genres.delete',
        ];
    }

    /**
     * @return array<class-string>
     */
    public function providers(): array
    {
        return [
            MusicServiceProvider::class,
        ];
    }

    public function boot(): void
    {
        // Nothing to boot.
    }
}
