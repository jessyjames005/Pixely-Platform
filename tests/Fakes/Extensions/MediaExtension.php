<?php

declare(strict_types=1);

namespace Tests\Fakes\Extensions;

use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Manifest\ExtensionManifest;

final class MediaExtension implements ExtensionInterface
{
    public function manifest(): ExtensionManifest
    {
        return new ExtensionManifest(
            id: 'media',
            name: 'Media',
            version: '1.0.0',
            class: self::class,
        );
    }

    public function providers(): array
    {
        return [];
    }

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}
