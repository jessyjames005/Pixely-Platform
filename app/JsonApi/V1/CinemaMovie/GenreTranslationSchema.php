<?php

declare(strict_types=1);

namespace App\JsonApi\V1\CinemaMovie;

use App\Extensions\CinemaMovie\Models\GenreTranslation;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Schema;

final class GenreTranslationSchema extends Schema
{
    public static string $model = GenreTranslation::class;
    protected static string $resourceType = 'cinema-genre-translations';

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
            Str::make('name')->sortable(),
            BelongsTo::make('genre')->type('cinema-genres'),
        ];
    }
}
