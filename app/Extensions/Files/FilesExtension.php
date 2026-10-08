<?php

declare(strict_types=1);

namespace App\Extensions\Files;

use App\Core\Extensions\Capabilities\Contracts\ExtensionSettingsInterface;
use App\Core\Extensions\Configuration\ExtensionConfigurableInterface;
use App\Core\Extensions\Capabilities\Contracts\ExtensionNavigationInterface;
use App\Core\Extensions\Capabilities\Contracts\ExtensionRoutesInterface;
use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Manifest\ExtensionManifest;
use App\Core\Extensions\Permissions\ExtensionPermissionsInterface;
use App\Core\Extensions\Translations\ExtensionTranslatableInterface;
use App\Extensions\Files\Providers\FilesServiceProvider;

/**
 * Files extension: shared upload validation and processing rules
 * (max size, allowed types, batch limits, thumbnails), consumed by
 * other extensions via a declared dependency — plus its own standalone
 * API and admin screen for browsing/uploading/deleting files directly.
 */
final class FilesExtension implements ExtensionInterface, ExtensionConfigurableInterface, ExtensionSettingsInterface, ExtensionNavigationInterface, ExtensionRoutesInterface, ExtensionPermissionsInterface, ExtensionTranslatableInterface
{
    public function manifest(): ExtensionManifest
    {
        return new ExtensionManifest(
            id: 'files',
            name: 'Files',
            version: '1.0.0',
            minimum_kernel_version: '1.0.0',
            class: self::class,
            path: 'app/Extensions/Files',
            dependencies: [],
            surfaces: ['admin', 'api'],
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function settings(): array
    {
        return [
            'max_file_size_kb' => [
                'type' => 'integer',
                'label' => 'Maximum file size (KB)',
                'default' => 5120,
                'min' => 1,
            ],
            'max_files_per_upload' => [
                'type' => 'integer',
                'label' => 'Maximum files per upload',
                'default' => 5,
                'min' => 1,
            ],
            'thumbnail_width' => [
                'type' => 'integer',
                'label' => 'Thumbnail width',
                'default' => 300,
                'min' => 1,
            ],
            'thumbnail_height' => [
                'type' => 'integer',
                'label' => 'Thumbnail height',
                'default' => 300,
                'min' => 1,
            ],
            'allowed_mimes' => [
                'type' => 'string[]',
                'label' => 'Allowed MIME extensions',
                'default' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            ],
        ];
    }

    public function defaultConfiguration(): array
    {
        return [
            'max_file_size_kb' => 5120, // 5 MB
            'allowed_mimes' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'max_files_per_upload' => 5,
            'thumbnail_width' => 300,
            'thumbnail_height' => 300,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function navigation(): array
    {
        return [[
            'id' => 'files',
            'label' => 'Files',
            'to' => '/admin/files',
            'icon' => 'mdi-file-multiple-outline',
            'permission' => 'files.view',
            'surface' => 'admin',
            'order' => 30,
            'extensionId' => 'files',
        ]];
    }

    /**
     * @return array<int, string>
     */
    public function declaredPermissions(): array
    {
        return [
            'files.view',
            'files.manage',
            'files.delete',
        ];
    }

    /**
     * @return list<array{file:string}>
     */
    public function routes(): array
    {
        return [['file' => 'app/Extensions/Files/API/routes.php']];
    }

    public function providers(): array
    {
        return [
            FilesServiceProvider::class,
        ];
    }

    public function boot(): void
    {
        // Nothing to boot.
    }

    public function translationsPath(): string
    {
        return base_path('app/Extensions/Files/lang');
    }
}
