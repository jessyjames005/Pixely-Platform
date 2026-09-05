<?php

declare(strict_types=1);

namespace App\Extensions\Translations;

use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Manifest\ExtensionManifest;
use App\Core\Extensions\Permissions\ExtensionPermissionsInterface;
use App\Extensions\Translations\Providers\TranslationsServiceProvider;

/**
 * Translations extension: browse and edit translation strings for
 * Core and any extension implementing ExtensionTranslatableInterface.
 */
final class TranslationsExtension implements ExtensionInterface, ExtensionPermissionsInterface
{
    public function manifest(): ExtensionManifest
    {
        return new ExtensionManifest(
            id: 'translations',
            name: 'Translations',
            version: '1.0.0',
            class: self::class,
            path: 'app/Extensions/Translations',
            dependencies: [],
        );
    }

    /**
     * @return array<int, string>
     */
    public function declaredPermissions(): array
    {
        return [
            'translations.strings.view',
            'translations.strings.manage',
        ];
    }

    public function providers(): array
    {
        return [
            TranslationsServiceProvider::class,
        ];
    }

    public function boot(): void
    {
        // Nothing to boot.
    }
}
