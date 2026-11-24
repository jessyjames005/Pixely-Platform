<?php

/**
 * JSON:API schema for the Music track resource (`music-tracks`).
 *
 * Exposes the catalogue fields plus the artist/album/genre/featured-artist
 * relationships used by the administration screen. The rights gate is enforced
 * at the model layer (defence-in-depth), so this schema stays a plain mapping.
 */

declare(strict_types=1);

namespace App\JsonApi\V1\Music;

use App\Extensions\Music\Models\Track;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsToMany;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

final class TrackSchema extends Schema
{
    public static string $model = Track::class;

    protected static string $resourceType = 'music-tracks';

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
            Number::make('trackNumber')->sortable(),
            Number::make('discNumber'),
            Number::make('durationMs'),
            Str::make('sourceType')->sortable(),
            Str::make('filePath')->readOnlyOnUpdate(),
            Str::make('mime'),
            Number::make('size'),
            Number::make('bitrate'),
            Number::make('sampleRate'),
            Number::make('channels'),
            Str::make('sha256')->readOnlyOnUpdate(),
            Str::make('youtubeVideoId'),
            Str::make('rightsStatus')->sortable(),
            Str::make('rightsNote'),
            Str::make('visibility')->sortable(),
            Str::make('status')->sortable(),
            Str::make('errorCode'),
            BelongsTo::make('artist')->type('music-artists'),
            BelongsTo::make('album')->type('music-albums'),
            BelongsToMany::make('genres')->type('music-genres'),
            BelongsToMany::make('featuredArtists')->type('music-artists'),
        ];
    }

    public function filters(): array
    {
        return [
            Where::make('title')
                ->using('like')
                ->deserializeUsing(static fn (string $value): string => '%' . $value . '%'),
            Where::make('sourceType'),
            Where::make('rightsStatus'),
            Where::make('visibility'),
            Where::make('status'),
        ];
    }

    public function pagination(): Paginator
    {
        return PagePagination::make()->withDefaultPerPage(20);
    }
}
