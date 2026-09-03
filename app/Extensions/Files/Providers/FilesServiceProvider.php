<?php

declare(strict_types=1);

namespace App\Extensions\Files\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Registers Files extension services.
 *
 * No routes of its own for now — Files is consumed as a service by
 * other extensions (e.g. Gallery), not exposed as a standalone API.
 */
final class FilesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Nothing to boot yet — services are resolved directly by
        // consuming extensions via the container.
    }
}
