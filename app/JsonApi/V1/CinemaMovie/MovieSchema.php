<?php

declare(strict_types=1);

namespace App\JsonApi\V1\CinemaMovie;

use App\Extensions\CinemaMovie\Models\Movie;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsToMany;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

final class MovieSchema extends Schema
{
    public static string $model = Movie::class;
    protected static string $resourceType = 'cinema-movies';
    protected int $maxDepth = 2;

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
            Str::make('slug')->readOnlyOnUpdate(),
            Str::make('originalTitle'),
            Str::make('originalLanguage'),
            DateTime::make('releaseDate')->sortable(),
            Number::make('runtimeMinutes')->sortable(),
            Str::make('ageRating'),
            Str::make('productionStatus')->sortable(),
            Str::make('publicationStatus')->sortable(),
            Number::make('budget'),
            Number::make('revenue'),
            Number::make('voteAverage'),
            Number::make('voteCount'),
            HasMany::make('translations')->type('cinema-movie-translations'),
            BelongsToMany::make('genres')->type('cinema-genres'),
        ];
    }

    public function filters(): array
    {
        return [
            Where::make('publicationStatus'),
            Where::make('productionStatus'),
            Where::make('slug'),
        ];
    }

    public function pagination(): Paginator
    {
        return PagePagination::make()->withDefaultPerPage(20);
    }
}
