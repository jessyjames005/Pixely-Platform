<?php

declare(strict_types=1);

namespace App\Extensions\Files\Services;

use App\Core\Extensions\Configuration\ExtensionConfigurationRepositoryInterface;
use App\Extensions\Files\FilesExtension;
use Illuminate\Http\UploadedFile;

/**
 * Validates uploaded files against the Files extension's configured
 * rules (max size, allowed types, batch count).
 *
 * Configuration is read fresh on each validation call (merging
 * defaults with any stored override), so admin changes to Files
 * settings take effect immediately without a restart.
 */
final class FileUploadValidator
{
    public function __construct(
        private readonly ExtensionConfigurationRepositoryInterface $configRepository,
    ) {
    }

    /**
     * @return array<string, mixed> the effective configuration (defaults + overrides)
     */
    public function configuration(): array
    {
        $extension = new FilesExtension();

        return array_replace(
            $extension->defaultConfiguration(),
            $this->configRepository->load('files'),
        );
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function assertValid(UploadedFile $file): void
    {
        $config = $this->configuration();

        $maxSizeBytes = (int) $config['max_file_size_kb'] * 1024;
        if ($file->getSize() > $maxSizeBytes) {
            throw new \InvalidArgumentException(
                "File exceeds the maximum allowed size of {$config['max_file_size_kb']} KB.",
            );
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $allowedMimes = array_map('strtolower', $config['allowed_mimes']);

        if (! in_array($extension, $allowedMimes, true)) {
            throw new \InvalidArgumentException(
                "File type '.{$extension}' is not allowed. Allowed types: " . implode(', ', $allowedMimes) . '.',
            );
        }
    }

    /**
     * @param UploadedFile[] $files
     * @throws \InvalidArgumentException
     */
    public function assertValidBatch(array $files): void
    {
        $config = $this->configuration();

        if (count($files) > (int) $config['max_files_per_upload']) {
            throw new \InvalidArgumentException(
                "Too many files in one upload. Maximum allowed: {$config['max_files_per_upload']}.",
            );
        }

        foreach ($files as $file) {
            $this->assertValid($file);
        }
    }
}
