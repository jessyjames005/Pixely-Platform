<?php

declare(strict_types=1);

namespace App\Core\Versioning;

/**
 * Provides the current Pixely Platform Kernel version.
 */
final class KernelVersionProvider
{
    public function getVersion(): string
    {
        return (string) config('pixely.kernel_version');
    }
}
