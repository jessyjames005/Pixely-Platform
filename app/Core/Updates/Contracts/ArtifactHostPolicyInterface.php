<?php

declare(strict_types=1);

namespace App\Core\Updates\Contracts;

use App\Core\Updates\Enums\ArtifactProvider;

/** Defines the HTTPS hosts permitted for each artifact delivery provider. */
interface ArtifactHostPolicyInterface
{
    /** Return true only when the URL host is explicitly allowed for this provider. */
    public function allows(ArtifactProvider $provider, string $url): bool;
}
