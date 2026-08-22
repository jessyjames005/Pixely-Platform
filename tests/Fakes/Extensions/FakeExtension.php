<?php

declare(strict_types=1);

namespace Tests\Fakes\Extensions;

use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Manifest\ExtensionManifest;

/**
 * Fake extension used for unit testing.
 */
final class FakeExtension implements ExtensionInterface
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
        );
    }

    /**
     * Return the Laravel service providers.
     *
     * @return array<class-string>
     */
    public function providers(): array
    {
        return [];
    }

    /**
     * Boot the extension.
     */
    public function boot(): void
    {
        // Nothing to boot.
    }
}
