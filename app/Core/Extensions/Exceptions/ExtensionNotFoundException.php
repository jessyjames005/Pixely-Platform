<?php

declare(strict_types=1);

namespace App\Core\Extensions\Exceptions;

use RuntimeException;

/**
 * Thrown when an extension cannot be found.
 */
final class ExtensionNotFoundException extends RuntimeException
{
}
