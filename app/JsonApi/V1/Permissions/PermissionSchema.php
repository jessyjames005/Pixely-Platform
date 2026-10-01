<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Permissions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation as EloquentRelation;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Spatie\Permission\Models\Permission;

final class PermissionSchema extends Schema
{
    public static string $model = Permission::class;

    protected $defaultSort = 'name';

    public function authorizable(): bool
    {
        return false;
    }

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('name')->sortable(),
            Boolean::make('isCore', 'is_core')
                ->serializeUsing(static fn ($value): bool => (bool) $value),
        ];
    }

    public function indexQuery(?Request $request, Builder $query): Builder
    {
        return $query->where('guard_name', 'web');
    }

    public function relatableQuery(?Request $request, EloquentRelation $query): EloquentRelation
    {
        return $query->where('guard_name', 'web');
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make()->withDefaultPerPage(20);
    }
}
