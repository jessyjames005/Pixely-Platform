<?php

declare(strict_types=1);

namespace App\Extensions\Files;

use App\Core\Extensions\Configuration\ExtensionConfigurableInterface;
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
final class FilesExtension implements ExtensionInterface, ExtensionConfigurableInterface, ExtensionPermissionsInterface, ExtensionTranslatableInterface
{
    public function manifest(): ExtensionManifest
    {
        return new ExtensionManifest(
            id: 'files',
            name: 'Files',
            version: '1.0.0',
            class: self::class,
            path: 'app/Extensions/Files',
            dependencies: [],
        );
    }

    /**
     * @return array<string, mixed>
     */
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
