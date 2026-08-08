<?php

declare(strict_types=1);

namespace App\Core\Extensions\Exceptions;

use RuntimeException;

/**
 * Thrown when an extension dependency cannot be resolved.
 */
class ExtensionDependencyException extends RuntimeException
{
}
