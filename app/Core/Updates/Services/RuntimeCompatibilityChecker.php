<?php

declare(strict_types=1);

namespace App\Core\Updates\Services;

use App\Core\Updates\DTO\UpdateCandidate;
use Composer\Semver\Semver;
use Illuminate\Foundation\Application;

/** Provides actual runtime versions and evaluates signed release constraints locally. */
final class RuntimeCompatibilityChecker
{
    /** @return array<string, string> */
    public function runtimeVersions(): array
    {
        return [
            'php' => PHP_VERSION,
            'laravel' => Application::VERSION,
            'pixely' => (string) config('pixely.kernel_version'),
        ];
    }

    /**
     * @param array<string, string> $runtimeVersions
     */
    public function supports(UpdateCandidate $candidate, array $runtimeVersions): bool
    {
        if ($candidate->runtimeConstraints === []) {
            return false;
        }

        foreach ($candidate->runtimeConstraints as $runtime => $constraint) {
            if (
                !in_array($runtime, ['php', 'laravel', 'pixely'], true)
                || trim($constraint) === ''
                || !array_key_exists($runtime, $runtimeVersions)
                || trim($runtimeVersions[$runtime]) === ''
            ) {
                return false;
            }

            try {
                if (!Semver::satisfies($runtimeVersions[$runtime], $constraint)) {
                    return false;
                }
            } catch (\InvalidArgumentException | \UnexpectedValueException) {
                return false;
            }
        }

        return true;
    }
}
