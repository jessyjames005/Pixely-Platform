<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Photos;

use App\Extensions\Gallery\Models\Photo;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

final class PhotoSchema extends Schema
{
    public static string $model = Photo::class;

    public function authorizable(): bool
    {
        return false;
    }

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('title')->sortable(),
            Str::make('filename')->readOnlyOnUpdate(),
            Str::make('thumbnailFilename')->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            Where::make('title')
                ->using('like')
                ->deserializeUsing(static fn (string $value): string => '%' . $value . '%'),
        ];
    }

    public function pagination(): Paginator
    {
        return PagePagination::make()->withDefaultPerPage(20);
    }
}
