<?php

declare(strict_types=1);

namespace App\Core\Extensions\Capabilities\Enum;

/**
 * Capabilities an extension can expose to the Pixely Platform.
 */
enum ExtensionCapability: string
{
    case Navigation = 'navigation';
    case Routes = 'routes';
    case Blocks = 'blocks';
    case Settings = 'settings';
    case Permissions = 'permissions';
}
