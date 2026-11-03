<?php

declare(strict_types=1);

namespace App\Core\Updates\DTO;

/**
 * One approved or simulated operation in an update plan.
 */
final readonly class UpdatePlanItem
{
    public function __construct(
        public string $packageId,
        public string $fromVersion,
        public string $toVersion,
        public UpdateCandidate $candidate,
        public bool $requiresDowntime = false,
    ) {
    }
}
