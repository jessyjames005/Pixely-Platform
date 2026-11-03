<?php

declare(strict_types=1);

namespace App\Core\Extensions\Installer;

use Illuminate\Support\Facades\File;

final class ExtensionInstallerFilesystem
{
    public function moveDirectory(string $source, string $destination, string $action): void
    {
        try {
            $moved = File::moveDirectory($source, $destination);
        } catch (\Throwable $exception) {
            throw new \RuntimeException(
                "{$action} failed for [{$source}] to [{$destination}]: {$exception->getMessage()}",
                previous: $exception,
            );
        }

        if (! $moved || ! $this->directoryExists($destination) || $this->directoryExists($source)) {
            throw new \RuntimeException("{$action} failed for [{$source}] to [{$destination}].");
        }
    }

    public function deleteDirectory(string $path, string $action): void
    {
        if (! is_dir($path)) {
            return;
        }

        try {
            $deleted = File::deleteDirectory($path);
        } catch (\Throwable $exception) {
            throw new \RuntimeException(
                "{$action} failed for [{$path}]: {$exception->getMessage()}",
                previous: $exception,
            );
        }

        if (! $deleted || $this->directoryExists($path)) {
            throw new \RuntimeException("{$action} failed for [{$path}].");
        }
    }

    /**
     * @return array{errors: list<string>, filesystem_may_be_inconsistent: bool}
     */
    public function rollback(
        ?string $targetPath,
        ?string $backupPath,
        bool $replacementAttempted,
        string $stagingDir,
        callable $refreshAutoloader,
    ): array {
        $errors = [];
        $refreshRequired = false;
        $filesystemMayBeInconsistent = false;

        if ($replacementAttempted && $targetPath !== null && is_dir($targetPath)) {
            $refreshRequired = true;
            try {
                $this->deleteDirectory($targetPath, 'Removing the failed live extension');
            } catch (\Throwable $exception) {
                $errors[] = $exception->getMessage();
                $filesystemMayBeInconsistent = true;
            }
        }

        if ($backupPath !== null && is_dir($backupPath) && $targetPath !== null) {
            $refreshRequired = true;
            if (is_dir($targetPath)) {
                $errors[] = "Cannot restore backup [{$backupPath}] because live path [{$targetPath}] still exists.";
                $filesystemMayBeInconsistent = true;
            } else {
                try {
                    $this->moveDirectory($backupPath, $targetPath, 'Restoring the extension backup');
                } catch (\Throwable $exception) {
                    $errors[] = $exception->getMessage();
                    $filesystemMayBeInconsistent = true;
                }
            }
        }

        if ($refreshRequired) {
            try {
                $refreshAutoloader();
            } catch (\Throwable $exception) {
                $errors[] = 'Refreshing the autoloader after filesystem rollback failed: ' . $exception->getMessage();
            }
        }

        try {
            $this->deleteDirectory($stagingDir, 'Removing extension staging files');
        } catch (\Throwable $exception) {
            $errors[] = $exception->getMessage();
        }

        return [
            'errors' => $errors,
            'filesystem_may_be_inconsistent' => $filesystemMayBeInconsistent,
        ];
    }

    /**
     * @param list<string> $rollbackErrors
     */
    public function failure(
        \Throwable $primaryFailure,
        array $rollbackErrors,
        ?string $backupPath,
        bool $databaseWorkStarted,
        bool $committed,
        ?string $extensionId,
        bool $filesystemMayBeInconsistent,
    ): \RuntimeException {
        $message = $primaryFailure->getMessage();

        if ($committed && $extensionId !== null) {
            $message = "Extension [{$extensionId}] was installed, but post-install cleanup failed: {$message}";
        }

        if ($rollbackErrors !== []) {
            $message .= "\nFilesystem rollback/cleanup errors:\n- " . implode("\n- ", $rollbackErrors);
            if ($filesystemMayBeInconsistent) {
                $message .= "\nThe extension may be left in an inconsistent filesystem state.";
            }
            if (! $committed && $backupPath !== null && is_dir($backupPath)) {
                $message .= " The original extension backup remains at [{$backupPath}].";
            }
        }

        if ($databaseWorkStarted && ! $committed) {
            $message .= "\nDatabase recovery warning: migrations or upgrade steps may have partially applied "
                . "and are not automatically rolled back. Back up the database and assess it manually "
                . "before retrying or restoring.";
        }

        return new \RuntimeException($message, previous: $primaryFailure);
    }

    private function directoryExists(string $path): bool
    {
        return is_dir($path);
    }
}
