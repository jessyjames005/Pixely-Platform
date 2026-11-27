<?php

/**
 * JSON:API schema for the Music artist resource (`music-artists`).
 *
 * Artists are the primary browse entity. Both `slug` and `normalizedName` are
 * maintained by the model on save, so they are read-only on update.
 */

declare(strict_types=1);

namespace App\JsonApi\V1\Music;

use App\Extensions\Music\Models\Artist;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

final class ArtistSchema extends Schema
{
    public static string $model = Artist::class;

    protected static string $resourceType = 'music-artists';

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
            Str::make('sortName'),
            Str::make('slug')->readOnlyOnUpdate(),
            Str::make('normalizedName')->readOnly(),
            Str::make('bio'),
            HasMany::make('albums')->type('music-albums'),
            HasMany::make('tracks')->type('music-tracks'),
            BelongsTo::make('image')->type('files'),
        ];
    }

    public function filters(): array
    {
        return [
            Where::make('name')
                ->using('like')
                ->deserializeUsing(static fn (string $value): string => '%' . $value . '%'),
            Where::make('normalizedName'),
            Where::make('slug'),
        ];
    }

    public function pagination(): Paginator
    {
        return PagePagination::make()->withDefaultPerPage(20);
    }
}
