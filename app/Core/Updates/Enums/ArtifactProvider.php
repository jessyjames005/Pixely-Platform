<?php

declare(strict_types=1);

namespace App\Core\Updates\Enums;

/**
 * Identifies where an update artifact is physically hosted.
 *
 * The official Pixely Registry remains the source of release metadata;
 * this enum only describes the artifact delivery provider.
 */
enum ArtifactProvider: string
{
    case Pixely = 'pixely';
    case GitHub = 'github';
    case GitLab = 'gitlab';
}
