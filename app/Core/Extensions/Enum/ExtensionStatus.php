<?php

declare(strict_types=1);

namespace App\Core\Extensions\Enum;

/**
 * Represents the current status of an extension.
 */
enum ExtensionStatus: string
{
    case Enabled = 'enabled';

    case Disabled = 'disabled';
}
