<?php

declare(strict_types=1);

namespace Tests\Fakes\Extensions;

use App\Core\Extensions\Configuration\ExtensionConfigurableInterface;
use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Manifest\ExtensionManifest;

/**
 * Fake gallery extension providing configuration.
 */
final class GalleryExtension implements ExtensionInterface, ExtensionConfigurableInterface
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

    /**
     * Return the extension default configuration.
     *
     * @return array<string, mixed>
     */
    public function defaultConfiguration(): array
    {
        return [
            'enabled' => true,
            'gallery' => [
                'per_page' => 20,
            ],
        ];
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
