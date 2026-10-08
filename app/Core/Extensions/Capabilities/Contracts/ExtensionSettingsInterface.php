<?php

declare(strict_types=1);

namespace App\Core\Extensions\Capabilities\Contracts;

/**
 * Declares a typed settings schema for an extension.
 *
 * This complements ExtensionConfigurableInterface: defaults remain the
 * persistence/runtime source, while this contract describes how settings
 * should be presented and validated by Platform surfaces.
 */
interface ExtensionSettingsInterface
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function settings(): array;
}
