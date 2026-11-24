<?php

/**
 * Music artist catalogue model.
 *
 * Artists are the primary browse entity. `normalized_name` is maintained by the
 * service layer to make de-duplication accent/case-insensitive, and `slug` backs
 * stable public URLs.
 */

declare(strict_types=1);

namespace App\Extensions\Music\Models;

use App\Extensions\Files\Models\File;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string|null $sort_name
 * @property string $slug
 * @property string $normalized_name
 * @property string|null $bio
 * @property int|null $image_file_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Album> $albums
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Track> $tracks
 */
final class Artist extends Model
{
    use HasFactory;

    protected $table = 'music_artists';

    protected $fillable = [
        'name',
        'sort_name',
        'slug',
        'normalized_name',
        'bio',
        'image_file_id',
    ];

    /**
     * Keep the slug and the normalized name in sync automatically so callers
     * never have to remember to set them.
     */
    protected static function booted(): void
    {
        static::saving(function (Artist $artist): void {
            if (blank($artist->normalized_name) && filled($artist->name)) {
                $artist->normalized_name = self::normalizeName($artist->name);
            }

            if (blank($artist->slug) && filled($artist->name)) {
                $artist->slug = Str::slug($artist->name) ?: 'artist';
            }
        });
    }

    /**
     * Normalize an artist name for de-duplication: lowercase, trim, collapse
     * whitespace and strip accents so "Beyoncé" and "Beyonce" match.
     */
    public static function normalizeName(string $name): string
    {
        $name = Str::lower(trim($name));
        $name = Str::of($name)->ascii()->toString();

        return trim((string) preg_replace('/\s+/u', ' ', $name));
    }

    /**
     * @return HasMany<Album, $this>
     */
    public function albums(): HasMany
    {
        return $this->hasMany(Album::class);
    }

    /**
     * @return HasMany<Track, $this>
     */
    public function tracks(): HasMany
    {
        return $this->hasMany(Track::class);
    }

    /**
     * @return BelongsTo<File, $this>
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class, 'image_file_id');
    }
}
