<?php

declare(strict_types=1);

namespace App\Extensions\Gallery;

use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Manifest\ExtensionManifest;
use App\Extensions\Gallery\Providers\GalleryServiceProvider;

/**
 * Gallery extension.
 */
final class GalleryExtension implements ExtensionInterface
{
    /**
     * Return the extension manifest.
     */
    public function manifest(): ExtensionManifest
    {
        return new ExtensionManifest(
            id: 'gallery',
            name: 'Gallery',
            version: '1.0.0',
            class: self::class,
            path: 'app/Extensions/Gallery',
            dependencies: [],
        );
    }

    /**
     * Return the Laravel service providers used by the extension.
     *
     * @return array<class-string>
     */
    public function providers(): array
    {
        return [
            GalleryServiceProvider::class,
        ];
    }

    /**
     * Boot the extension.
     */
    public function boot(): void
    {
        // Nothing to boot.
    }
}
