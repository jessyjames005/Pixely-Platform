<?php

declare(strict_types=1);

namespace App\Core\Extensions\Contracts;

use App\Core\Extensions\Manifest\ExtensionManifest;

/**
 * Defines the contract implemented by every Pixely extension.
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
