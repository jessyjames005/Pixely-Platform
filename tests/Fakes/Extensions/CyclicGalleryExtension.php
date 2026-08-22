<?php

declare(strict_types=1);

namespace Tests\Fakes\Extensions;

use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Manifest\ExtensionManifest;

/**
 * Fake gallery extension participating in a dependency cycle.
 */
final class CyclicGalleryExtension implements ExtensionInterface
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
            dependencies: [
                'media',
            ],
        );
    }

    public function providers(): array
    {
        return [];
    }

    public function boot(): void
    {
        // Nothing to boot.
    }
}
