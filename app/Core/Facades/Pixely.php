<?php

declare(strict_types=1);

namespace App\Core\Facades;

use Illuminate\Support\Facades\Facade;
use App\Core\Kernel\Kernel;

/**
 * @method static void boot()
 * @method static void shutdown()
 * @method static bool isBooted()
 * @method static array extensions()
 * @method static void registerExtension($extension)
 */
final class Pixely extends Facade
{
    /**
     * Get the registered name of the component in the container.
     */
    protected static function getFacadeAccessor(): string
    {
        return Kernel::class;
    }
}
