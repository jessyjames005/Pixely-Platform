<?php

declare(strict_types=1);

namespace App\Extensions\Gallery\Upgrades;

use App\Core\Extensions\Versioning\ExtensionUpgradeStepInterface;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Extensions\Gallery\Models\Photo;

/**
 * Example schema-changing upgrade step: adds slug and file_size
 * columns, then backfills them for existing photos.
 */
final class AddSlugAndFileSizeStep implements ExtensionUpgradeStepInterface
{
    public function version(): string
    {
        return '1.0.2';
    }

    public function description(): string
    {
        return 'Add slug and file_size columns to photos, backfilled for existing rows.';
    }

    public function apply(): void
    {
        if (! Schema::hasColumn('photos', 'slug')) {
            Schema::table('photos', function (Blueprint $table): void {
                $table->string('slug')->nullable()->after('title');
                $table->unsignedBigInteger('file_size')->nullable()->after('filename');
            });
        }

        Photo::query()->whereNull('slug')->each(function (Photo $photo): void {
            $photo->update([
                'slug' => Str::slug($photo->title ?? 'photo-' . $photo->id) . '-' . $photo->id,
                'file_size' => Storage::disk('public')->exists($photo->filename)
                    ? Storage::disk('public')->size($photo->filename)
                    : null,
            ]);
        });
    }
}
