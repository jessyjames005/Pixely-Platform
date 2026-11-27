<?php

/**
 * JSON:API schema for the Music album resource (`music-albums`).
 *
 * Albums belong to a primary artist and group tracks. Slug is read-only on
 * update (it is generated from the title on create) to keep public URLs stable.
 */

declare(strict_types=1);

namespace App\JsonApi\V1\Music;

use App\Extensions\Music\Models\Album;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

final class AlbumSchema extends Schema
{
    public static string $model = Album::class;

    protected static string $resourceType = 'music-albums';

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
            Str::make('title')->sortable(),
            Str::make('slug')->readOnlyOnUpdate(),
            Number::make('releaseYear')->sortable(),
            Str::make('type')->sortable(),
            BelongsTo::make('artist')->type('music-artists'),
            HasMany::make('tracks')->type('music-tracks'),
            BelongsTo::make('cover')->type('files'),
        ];
    }

    public function filters(): array
    {
        return [
            Where::make('title')
                ->using('like')
                ->deserializeUsing(static fn (string $value): string => '%' . $value . '%'),
            Where::make('slug'),
            Where::make('type'),
            Where::make('releaseYear'),
        ];
    }

    public function pagination(): Paginator
    {
        return PagePagination::make()->withDefaultPerPage(20);
    }
}
