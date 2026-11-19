<?php

declare(strict_types=1);

namespace App\JsonApi\V1\CinemaMovie;

use App\Extensions\CinemaMovie\Models\MovieTranslation;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Schema;

final class MovieTranslationSchema extends Schema
{
    public static string $model = MovieTranslation::class;
    protected static string $resourceType = 'cinema-movie-translations';

    public static function type(): string
    {
        return static::$resourceType;
    }

    public function authorizable(): bool
    {
        return false;
    }

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('locale'),
            Str::make('title')->sortable(),
            Str::make('overview'),
            Str::make('tagline'),
            BelongsTo::make('movie')->type('cinema-movies'),
        ];
    }
}
