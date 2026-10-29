<?php

declare(strict_types=1);

namespace App\Core\Updates\DTO;

/**
 * Immutable result returned by a package-manager operation.
 */
final readonly class DependencyUpdateResult
{
    public function __construct(
        public string $manager,
        public string $operation,
        public bool $successful,
        public int $exitCode,
        public string $stdout,
        public string $stderr,
        public float $durationSeconds,
    ) {
    }

    /**
     * Return a concise, serialisable representation for reports and logs.
     * Sensitive command arguments are deliberately not included.
     *
     * @return array<string, bool|float|int|string>
     */
    public function toArray(): array
    {
        return [
            'manager' => $this->manager,
            'operation' => $this->operation,
            'successful' => $this->successful,
            'exit_code' => $this->exitCode,
            'duration_seconds' => $this->durationSeconds,
            'stdout' => $this->stdout,
            'stderr' => $this->stderr,
        ];
    }
}
