<?php

declare(strict_types=1);

namespace App\Extensions\Files\Services;

use App\Media\Contracts\ImageProcessorInterface;
use App\Media\Contracts\StorageInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Orchestrates a validated file upload: validation, storage, and
 * (for images) thumbnail generation — the single entry point other
 * extensions should use instead of touching StorageInterface directly.
 */
final class FileUploadService
{
    public function __construct(
        private readonly FileUploadValidator $validator,
        private readonly StorageInterface $storage,
        private readonly ImageProcessorInterface $imageProcessor,
    ) {
    }

    /**
     * @return array{path: string, thumbnail_path: ?string}
     * @throws \InvalidArgumentException
     */
    public function upload(UploadedFile $file, string $directory = 'photos', bool $generateThumbnail = true): array
    {
        $this->validator->assertValid($file);

        $path = $this->storage->store($file, $directory);
        $thumbnailPath = null;

        if ($generateThumbnail && str_starts_with((string) $file->getMimeType(), 'image/')) {
            $config = $this->validator->configuration();

            $sourceFullPath = Storage::disk('public')->path($path);
            $thumbnailRelativePath = $directory . '/thumbnails/' . basename($path);
            $thumbnailFullPath = Storage::disk('public')->path($thumbnailRelativePath);

            Storage::disk('public')->makeDirectory($directory . '/thumbnails');

            $this->imageProcessor->generateThumbnail(
                $sourceFullPath,
                $thumbnailFullPath,
                (int) $config['thumbnail_width'],
                (int) $config['thumbnail_height'],
            );

            $thumbnailPath = $thumbnailRelativePath;
        }

        return ['path' => $path, 'thumbnail_path' => $thumbnailPath];
    }

    public function delete(string $path, ?string $thumbnailPath = null): void
    {
        $this->storage->delete($path);

        if ($thumbnailPath !== null) {
            $this->storage->delete($thumbnailPath);
        }
    }
}
