<?php

declare(strict_types=1);

namespace App\Extensions\CinemaMovie\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $movie_id
 * @property string $locale
 * @property string $title
 * @property string|null $overview
 * @property string|null $tagline
 */
final class MovieTranslation extends Model
{
    protected $table = 'cinema_movie_translations';

    protected $fillable = ['movie_id', 'locale', 'title', 'overview', 'tagline'];

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }
}
