<?php

declare(strict_types=1);

namespace App\Extensions\Music\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Registers Music extension services.
 * Extension migrations are run by Pixely's extension migration runner.
 * API routes are registered centrally by the Extension SDK v2.
 */
final class MusicServiceProvider extends ServiceProvider
{
}
