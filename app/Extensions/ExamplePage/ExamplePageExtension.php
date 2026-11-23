<?php

declare(strict_types=1);

namespace App\Extensions\ExamplePage;

use App\Core\Extensions\Capabilities\Contracts\ExtensionRoutesInterface;
use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Manifest\ExtensionManifest;

/**
 * Minimal reference extension that publishes a public page through the
 * Website Engine PageModel contract.
 *
 * It intentionally implements only the required ExtensionInterface plus the
 * optional ExtensionRoutesInterface (to expose its public endpoint). It
 * declares no permissions, blocks, navigation, configuration, or migrations,
 * so it stays a minimal, side-effect-free example of the integration contract.
 */
final class ExamplePageExtension implements ExtensionInterface, ExtensionRoutesInterface
{
    public function manifest(): ExtensionManifest
    {
        return new ExtensionManifest(
            id: 'example-page',
            name: 'Example Page',
            version: '1.0.0',
            minimum_kernel_version: '1.0.0',
            class: self::class,
            path: 'app/Extensions/ExamplePage',
            surfaces: ['public', 'api'],
        );
    }

    /**
     * Declare the extension's public route file.
     *
     * Core registers it under the api/v1 prefix with the surface:api middleware.
     *
     * @return list<array{file:string}>
     */
    public function routes(): array
    {
        return [['file' => 'app/Extensions/ExamplePage/API/routes.php']];
    }

    /**
     * No service providers are required for this minimal example.
     *
     * @return array<class-string>
     */
    public function providers(): array
    {
        return [];
    }

    public function boot(): void
    {
        // Nothing to boot.
    }
}