<?php

declare(strict_types=1);

namespace App\JsonApi\V1\CinemaMovie;

use App\Extensions\CinemaMovie\Models\Genre;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsToMany;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

final class GenreSchema extends Schema
{
    public static string $model = Genre::class;
    protected static string $resourceType = 'cinema-genres';

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
            HasMany::make('translations')->type('cinema-genre-translations'),
            BelongsToMany::make('movies')->type('cinema-movies'),
        ];
    }

    public function pagination(): Paginator
    {
        return PagePagination::make()->withDefaultPerPage(20);
    }
}
