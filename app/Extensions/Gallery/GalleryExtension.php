<?php

declare(strict_types=1);

namespace App\Extensions\Gallery;

use App\Core\Extensions\Capabilities\Contracts\ExtensionNavigationInterface;
use App\Core\Extensions\Capabilities\Contracts\ExtensionRoutesInterface;
use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Manifest\ExtensionManifest;
use App\Core\Extensions\Permissions\ExtensionPermissionsInterface;
use App\Core\Extensions\Translations\ExtensionTranslatableInterface;
use App\Core\Extensions\Versioning\ExtensionUpgradableInterface;
use App\Extensions\Gallery\Providers\GalleryServiceProvider;
use App\Extensions\Gallery\Upgrades\AddSlugAndFileSizeStep;
use App\Extensions\Gallery\Upgrades\FixPhotoDisplayStep;

final class GalleryExtension implements
    ExtensionInterface,
    ExtensionNavigationInterface,
    ExtensionRoutesInterface,
    ExtensionPermissionsInterface,
    ExtensionUpgradableInterface,
    ExtensionTranslatableInterface
{
    public function manifest(): ExtensionManifest
    {
        return new ExtensionManifest(
            id: 'gallery',
            name: 'Gallery',
            version: '1.0.0',
            minimum_kernel_version: '1.0.0',
            class: self::class,
            path: 'app/Extensions/Gallery',
            dependencies: [
                'files',
            ],
            surfaces: ['public', 'admin', 'api'],
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function navigation(): array
    {
        return [
            [
                'id' => 'gallery',
                'label' => 'Gallery',
                'route' => '/admin/gallery',
                'icon' => 'mdi-image-multiple',
                'permission' => 'gallery.photos.view',
                'surface' => 'admin',
                'order' => 20,
            ],
        ];
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

    /**
     * @return list<array{file:string}>
     */
    public function routes(): array
    {
        return [['file' => 'app/Extensions/Gallery/API/routes.php']];
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

    /**
     * @return array<int, \App\Core\Extensions\Versioning\ExtensionUpgradeStepInterface>
     */
    public function upgradeSteps(): array
    {
        return [
            new FixPhotoDisplayStep(),
            new AddSlugAndFileSizeStep(),
        ];
    }

    /**
     * Absolute path to Gallery's own translation files.
     */
    public function translationsPath(): string
    {
        return base_path('app/Extensions/Gallery/lang');
    }
}
