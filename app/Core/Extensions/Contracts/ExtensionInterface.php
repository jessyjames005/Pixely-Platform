<?php

declare(strict_types=1);

namespace App\Core\Extensions\Contracts;

use App\Core\Extensions\Manifest\ExtensionManifest;

/**
 * Represents a Pixely extension.
 */
interface ExtensionInterface
{
    public function manifest(): ExtensionManifest;

    /**
     * Register services.
     */
    public function register(): void;

    /**
     * Boot the extension.
     */
    public function boot(): void;
}
