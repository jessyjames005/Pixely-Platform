<?php

declare(strict_types=1);

namespace App\Core\Updates\Enums;

/**
 * Identifies the kind of component handled by the update system.
 */
enum UpdateTargetType: string
{
    case Platform = 'platform';
    case Extension = 'extension';
    case Theme = 'theme';
    case Plugin = 'plugin';
}
