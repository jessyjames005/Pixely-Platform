<?php

declare(strict_types=1);

namespace App\Core\Extensions\Contracts;

use App\Core\Extensions\Manifest\ExtensionManifest;

interface ExtensionInterface
{
    public function manifest(): ExtensionManifest;

    /**
     * Laravel service providers used by the extension.
     *
     * @return array<class-string>
     */
    public function providers(): array;

    public function boot(): void;
}
