<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Files;

use App\Extensions\Files\Models\File;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

final class FileSchema extends Schema
{
    public static string $model = File::class;

    public function authorizable(): bool
    {
        return false;
    }

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('path')->readOnly(),
            Str::make('thumbnail_path')->readOnly(),
            Str::make('original_name')->readOnly(),
            Str::make('mime_type')->readOnly(),
            Number::make('size')->readOnly(),
            Number::make('uploaded_by')->readOnly(),
            Str::make('url')->readOnly(),
            Str::make('thumbnail_url')->readOnly(),
        ];
    }

    public function pagination(): Paginator
    {
        return PagePagination::make()->withDefaultPerPage(20);
    }
}
