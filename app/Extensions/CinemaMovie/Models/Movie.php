<?php

declare(strict_types=1);

namespace App\Extensions\CinemaMovie\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property string $slug
 * @property string|null $original_title
 * @property string|null $original_language
 */
final class Movie extends Model
{
    use HasFactory;

    public const PRODUCTION_STATUSES = [
        'rumored',
        'planned',
        'in_production',
        'post_production',
        'released',
        'canceled',
    ];

    public const PUBLICATION_STATUSES = ['draft', 'published', 'archived'];

    protected $table = 'cinema_movies';

    protected $fillable = [
        'slug',
        'original_title',
        'original_language',
        'release_date',
        'runtime_minutes',
        'age_rating',
        'production_status',
        'publication_status',
        'budget',
        'revenue',
        'vote_average',
        'vote_count',
    ];

    protected function casts(): array
    {
        return [
            'release_date' => 'date:Y-m-d',
            'runtime_minutes' => 'integer',
            'budget' => 'integer',
            'revenue' => 'integer',
            'vote_average' => 'decimal:1',
            'vote_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Movie $movie): void {
            if (filled($movie->slug)) {
                return;
            }

            $base = Str::slug($movie->original_title ?: 'movie') ?: 'movie';
            $slug = $base;
            $suffix = 2;

            while (static::query()->where('slug', $slug)->exists()) {
                $slug = $base . '-' . $suffix;
                $suffix++;
            }

            $movie->slug = $slug;
        });
    }

    public function translations(): HasMany
    {
        return $this->hasMany(MovieTranslation::class);
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'cinema_genre_movie', 'movie_id', 'genre_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('publication_status', 'published');
    }
}
