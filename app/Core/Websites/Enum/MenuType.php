<?php

declare(strict_types=1);

namespace App\Core\Websites\Enum;

/**
 * Possible menu types.
 */
enum MenuType: string
{
    case PAGE = 'page';
    case EXTENSION = 'extension';
    case EXTERNAL = 'external';
}
