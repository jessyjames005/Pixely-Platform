<?php

declare(strict_types=1);

namespace App\Core\Extensions\State;

use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Enum\ExtensionStatus;

/**
 * Represents the runtime state of an extension.
 */
final readonly class ExtensionState
{
    /**
     * Create a new extension state.
     */
    public function __construct(
        public ExtensionInterface $extension,
        public ExtensionStatus $status,
    ) {
    }
}
