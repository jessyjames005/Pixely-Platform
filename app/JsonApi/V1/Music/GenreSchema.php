<?php

/**
 * JSON:API schema for the Music genre resource (`music-genres`).
 *
 * Genres are a flat, reusable taxonomy shared across tracks through the
 * `music_genre_track` pivot. `slug` is generated from the name on create.
 */

declare(strict_types=1);

namespace App\JsonApi\V1\Music;

use App\Extensions\Music\Models\Genre;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsToMany;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

final class GenreSchema extends Schema
{
    public static string $model = Genre::class;

    protected static string $resourceType = 'music-genres';

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
            Str::make('name')->sortable(),
            Str::make('slug')->readOnlyOnUpdate(),
            BelongsToMany::make('tracks')->type('music-tracks'),
        ];
    }

    public function filters(): array
    {
        return [
            Where::make('name')
                ->using('like')
                ->deserializeUsing(static fn (string $value): string => '%' . $value . '%'),
            Where::make('slug'),
        ];
    }

    public function pagination(): Paginator
    {
        return PagePagination::make()->withDefaultPerPage(20);
    }
}
