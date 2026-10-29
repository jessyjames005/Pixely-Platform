<?php

declare(strict_types=1);

namespace App\Core\Updates\DTO;

use App\Core\Updates\Enums\UpdateChannel;
use App\Core\Updates\Enums\UpdateTargetType;

/**
 * Describes the installed component and compatibility context for discovery.
 */
final readonly class UpdateQuery
{
    /**
     * @param array<string, string> $runtimeVersions Runtime constraints such as php, laravel and pixely.
     */
    public function __construct(
        public UpdateTargetType $type,
        public string $packageId,
        public string $installedVersion,
        public UpdateChannel $channel = UpdateChannel::Stable,
        public array $runtimeVersions = [],
    ) {
        if (trim($this->packageId) === '') {
            throw new \InvalidArgumentException('The package identifier cannot be empty.');
        }

        if (trim($this->installedVersion) === '') {
            throw new \InvalidArgumentException('The installed version cannot be empty.');
        }
    }
}
