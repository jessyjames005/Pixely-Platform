<?php

declare(strict_types=1);

namespace App\Core\Extensions\Configuration;

use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Configuration\ExtensionConfigurableInterface;

/**
 * Provides configuration values for an extension.
 */
final class ExtensionConfiguration
{
    /**
     * Create a new extension configuration.
     */
    public function __construct(
        private readonly ExtensionInterface $extension,
    ) {}

    /**

     * Return all configuration values.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if ($this->extension instanceof ExtensionConfigurableInterface) {
            return $this->extension->defaultConfiguration();
        }

        return [
            'enabled' => true,
        ];
    }
}
