<?php

declare(strict_types=1);

namespace App\Core\Extensions\Capabilities\Registry;

use App\Core\Extensions\Capabilities\Contracts\ExtensionBlocksInterface;
use App\Core\Extensions\Capabilities\Contracts\ExtensionNavigationInterface;
use App\Core\Extensions\Capabilities\Contracts\ExtensionRoutesInterface;
use App\Core\Extensions\Capabilities\Contracts\ExtensionSettingsInterface;
use App\Core\Extensions\Capabilities\Enum\ExtensionCapability;
use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Permissions\ExtensionPermissionsInterface;

/**
 * Resolves the capabilities exposed by registered extensions.
 */
final class ExtensionCapabilityRegistry
{
    /**
     * @return array<string, list<ExtensionCapability>>
     */
    public function all(iterable $extensions): array
    {
        $capabilities = [];

        foreach ($extensions as $extension) {
            if (! $extension instanceof ExtensionInterface) {
                continue;
            }

            $capabilities[$extension->manifest()->id] = $this->for($extension);
        }

        return $capabilities;
    }

    /**
     * @return list<ExtensionCapability>
     */
    public function for(ExtensionInterface $extension): array
    {
        $capabilities = [];

        if ($extension instanceof ExtensionNavigationInterface) {
            $capabilities[] = ExtensionCapability::Navigation;
        }

        if ($extension instanceof ExtensionRoutesInterface) {
            $capabilities[] = ExtensionCapability::Routes;
        }

        if ($extension instanceof ExtensionBlocksInterface) {
            $capabilities[] = ExtensionCapability::Blocks;
        }

        if ($extension instanceof ExtensionSettingsInterface) {
            $capabilities[] = ExtensionCapability::Settings;
        }

        if ($extension instanceof ExtensionPermissionsInterface) {
            $capabilities[] = ExtensionCapability::Permissions;
        }

        return $capabilities;
    }
}
