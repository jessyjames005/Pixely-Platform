<?php

declare(strict_types=1);

namespace App\Core\Extensions\Capabilities\Registry;

use App\Core\Extensions\Capabilities\Contracts\ExtensionRoutesInterface;
use App\Core\Extensions\Contracts\ExtensionInterface;
use Illuminate\Support\Facades\Route;
use RuntimeException;

/**
 * Registers API route files declared by enabled extensions.
 *
 * Route registration belongs to Core so extensions cannot bypass the
 * platform API prefix or the authoritative API surface middleware.
 */
final class ExtensionRouteRegistrar
{
    /**
     * Register all API route declarations exposed by an extension.
     */
    public function register(ExtensionInterface $extension): void
    {
        if (! $extension instanceof ExtensionRoutesInterface) {
            return;
        }

        foreach ($extension->routes() as $definition) {
            $file = $definition['file'] ?? null;

            if (! is_string($file) || $file === '') {
                throw new RuntimeException(
                    "Extension [{$extension->manifest()->id}] declared an invalid route file."
                );
            }

            $path = str_starts_with($file, DIRECTORY_SEPARATOR)
                ? $file
                : base_path(trim($file, '/'));

            if (! is_file($path)) {
                throw new RuntimeException(
                    "Extension [{$extension->manifest()->id}] route file not found: {$path}"
                );
            }

            Route::middleware([
                'api',
                'surface:api',
            ])->prefix('api/v1')->group($path);
        }
    }
}
