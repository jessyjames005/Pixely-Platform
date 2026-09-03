<?php

declare(strict_types=1);

namespace App\Extensions\Gallery;

use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Manifest\ExtensionManifest;
use App\Core\Extensions\Permissions\ExtensionPermissionsInterface;
use App\Extensions\Gallery\Providers\GalleryServiceProvider;

final class GalleryExtension implements ExtensionInterface, ExtensionPermissionsInterface
{
    public function manifest(): ExtensionManifest
    {
        return new ExtensionManifest(
            id: 'gallery',
            name: 'Gallery',
            version: '1.0.0',
            class: self::class,
            path: 'app/Extensions/Gallery',
            dependencies: [
                'files',
            ],
        );
    }

    /**
     * @return array<int, string>
     */
    public function declaredPermissions(): array
    {
        return [
            'gallery.photos.view',
            'gallery.photos.manage',
            'gallery.photos.delete',
        ];
    }

    public function providers(): array
    {
        return [
            GalleryServiceProvider::class,
        ];
    }

    public function boot(): void
    {
        // Nothing to boot.
    }
}
