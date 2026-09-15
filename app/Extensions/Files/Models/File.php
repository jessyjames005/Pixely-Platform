<?php

declare(strict_types=1);

namespace App\Extensions\Files\Models;

use Database\Factories\FileFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * A file uploaded through the standalone Files API.
 *
 * Note: this registry only covers uploads made through this
 * extension's own API. Gallery photos and user avatars are uploaded
 * via the shared FileUploadService directly and keep their own
 * storage (photos.filename, users.avatar_filename) — they are not
 * (yet) mirrored into this table.
 *
 * @property int $id
 * @property string $disk
 * @property string $path
 * @property string|null $thumbnail_path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size
 * @property int|null $uploaded_by
 */
final class File extends Model
{
    use HasFactory;

    protected $table = 'files';

    protected $fillable = [
        'disk',
        'path',
        'thumbnail_path',
        'original_name',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    /**
     * url/thumbnail_url are computed accessors, not real columns —
     * without $appends they wouldn't show up in the JSON response the
     * frontend relies on to render previews and download links.
     */
    protected $appends = ['url', 'thumbnail_url'];

    protected function url(): Attribute
    {
        return Attribute::get(fn () => Storage::disk($this->disk)->url($this->path));
    }

    protected function thumbnailUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->thumbnail_path
                ? Storage::disk($this->disk)->url($this->thumbnail_path)
                : null,
        );
    }

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    protected static function newFactory(): FileFactory
    {
        return FileFactory::new();
    }
}
