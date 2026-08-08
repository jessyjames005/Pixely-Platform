<?php

declare(strict_types=1);

namespace App\Core\Extensions\Exceptions;

/**
 * Thrown when a circular extension dependency is detected.
 */
final class ExtensionDependencyCycleException extends ExtensionDependencyException
{
}
