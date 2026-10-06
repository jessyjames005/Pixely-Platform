<?php

declare(strict_types=1);

namespace App\Core\Extensions\Contracts;

use App\Core\Extensions\Manifest\ExtensionManifest;
use App\Core\Extensions\Configuration\ExtensionConfigurableInterface;
use App\Core\Extensions\Configuration\ExtensionSettingsInterface;
use App\Core\Extensions\Contracts\ExtensionNavigationInterface;
use App\Core\Extensions\Contracts\ExtensionRouteProviderInterface;
use App\Core\Extensions\Contracts\ExtensionBlockProviderInterface;
use App\Core\Extensions\Permissions\ExtensionPermissionsInterface;

/**
 * Defines the contract implemented by every Pixely extension.
 *
 * Extensions may optionally implement additional capability contracts:
 * - ExtensionConfigurableInterface: for default configuration
 * - ExtensionSettingsInterface: for surface-specific settings schemas
 * - ExtensionNavigationInterface: for surface navigation items
 * - ExtensionRouteProviderInterface: for surface routes
 * - ExtensionBlockProviderInterface: for surface blocks
 * - ExtensionPermissionsInterface: for declaring permissions
 */
interface ExtensionInterface
{
    /**
     * Return the extension manifest.
     */
    public function manifest(): ExtensionManifest;

    /**
     * Return the Laravel service providers used by the extension.
     *
     * @return array<class-string>
     */
    public function providers(): array;

    /**
     * Boot the extension.
     */
    public function boot(): void;
}
