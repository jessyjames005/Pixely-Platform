<?php

declare(strict_types=1);

namespace Tests\Fakes\Extensions;

use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Manifest\ExtensionManifest;

/**
 * Fake media extension participating in a dependency cycle.
 */
final class CyclicMediaExtension implements ExtensionInterface
{
    /**
     * Return the extension manifest.
     */
    public function manifest(): ExtensionManifest
    {
        return new ExtensionManifest(
            id: 'media',
            name: 'Media',
            version: '1.0.0',
            class: self::class,
            path: 'app/Extensions/Gallery',
            dependencies: [
                'gallery',
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
