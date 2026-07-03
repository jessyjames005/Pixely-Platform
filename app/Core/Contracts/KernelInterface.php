<?php

declare(strict_types=1);

namespace App\Core\Contracts;

/**
 * Defines the entry point of the Pixely Platform.
 *
 * The Kernel is responsible for bootstrapping the platform
 * and orchestrating its lifecycle.
 */
interface KernelInterface
{
    /**
     * Boot the Pixely platform.
     */
    public function boot(): void;

    /**
     * Shutdown the Pixely platform.
     */
    public function shutdown(): void;

    /**
     * Indicates whether the platform has finished booting.
     */
    public function isBooted(): bool;
}