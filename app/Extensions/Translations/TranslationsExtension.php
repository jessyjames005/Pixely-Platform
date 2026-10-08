<?php

declare(strict_types=1);

namespace App\Extensions\Translations;

use App\Core\Extensions\Capabilities\Contracts\ExtensionNavigationInterface;
use App\Core\Extensions\Capabilities\Contracts\ExtensionRoutesInterface;
use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Manifest\ExtensionManifest;
use App\Core\Extensions\Permissions\ExtensionPermissionsInterface;
use App\Extensions\Translations\Providers\TranslationsServiceProvider;

/**
 * Translations extension: browse and edit translation strings for
 * Core and any extension implementing ExtensionTranslatableInterface.
 */
final class TranslationsExtension implements ExtensionInterface, ExtensionNavigationInterface, ExtensionRoutesInterface, ExtensionPermissionsInterface
{
    public function manifest(): ExtensionManifest
    {
        return new ExtensionManifest(
            id: 'translations',
            name: 'Translations',
            version: '1.0.0',
            minimum_kernel_version: '1.0.0',
            class: self::class,
            path: 'app/Extensions/Translations',
            dependencies: [],
            surfaces: ['admin', 'api'],
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function navigation(): array
    {
        return [[
            'id' => 'translations',
            'label' => 'Translations',
            'to' => '/admin/translations',
            'icon' => 'mdi-translate',
            'permission' => 'translations.strings.view',
            'surface' => 'admin',
            'order' => 90,
            'extensionId' => 'translations',
        ]];
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

    /**
     * @return list<array{file:string}>
     */
    public function routes(): array
    {
        return [['file' => 'app/Extensions/Translations/API/routes.php']];
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
