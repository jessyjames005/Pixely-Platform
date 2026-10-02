<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Roles;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation as EloquentRelation;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsToMany;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Spatie\Permission\Models\Role;

final class RoleSchema extends Schema
{
    public static string $model = Role::class;

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
            BelongsToMany::make('permissions')->type('permissions'),
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

    public function pagination(): Paginator
    {
        return PagePagination::make()->withDefaultPerPage(20);
    }
}
