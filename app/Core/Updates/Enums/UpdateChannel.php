<?php

declare(strict_types=1);

namespace App\Core\Updates\Enums;

/**
 * Defines the release stability channel requested by an installation.
 */
enum UpdateChannel: string
{
    case Stable = 'stable';
    case Beta = 'beta';
    case Development = 'dev';
}
