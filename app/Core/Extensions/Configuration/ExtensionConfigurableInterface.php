<?php

declare(strict_types=1);

namespace App\Core\Extensions\Configuration;

/**
 * Defines an optional contract for extensions providing configuration.
 */
interface ExtensionConfigurableInterface
{
    /**
     * Return the extension default configuration.
     *
     * @return array<string, mixed>
     */
    public function defaultConfiguration(): array;
}
