<?php

declare(strict_types=1);

namespace App\Core\Extensions;

use App\Core\Extensions\ExtensionManifest;

/**
 * Base contract for all Pixely extensions.
 *
 * An extension is a modular unit that can hook into the platform lifecycle.
 */
interface ExtensionInterface
{
    /**
     * Returns the extension manifest.
     */
    public function manifest(): ExtensionManifest;

    /**
     * Register the extension into the platform.
     */
    public function register(): void;

    /**
     * Boot logic executed when the platform starts.
     */
    public function boot(): void;
}
