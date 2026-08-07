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
    ) {}

    /**
     * Return a new state with the extension enabled.
     */
    public function enable(): self
    {
        return new self(
            $this->extension,
            ExtensionStatus::Enabled,
        );
    }

    /**
     * Return a new state with the extension disabled.
     */
    public function disable(): self
    {
        return new self(
            $this->extension,
            ExtensionStatus::Disabled,
        );
    }

    /**
     * Determine whether the extension is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->status === ExtensionStatus::Enabled;
    }
}
