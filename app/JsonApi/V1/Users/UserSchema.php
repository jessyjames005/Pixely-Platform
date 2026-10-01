<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Users;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation as EloquentRelation;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsToMany;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

final class UserSchema extends Schema
{
    public static string $model = User::class;

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
            Str::make('email')->sortable(),
            Str::make('password')->hidden(),
            BelongsToMany::make('roles')->type('roles'),
        ];
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
