<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Roles;

use LaravelJsonApi\Laravel\Http\Requests\ResourceQuery;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

final class RoleCollectionQuery extends ResourceQuery
{
    public function rules(): array
    {
        return [
            'fields' => ['nullable', 'array', JsonApiRule::fieldSets()],
            'include' => ['nullable', 'string', JsonApiRule::includePaths()],
            'page' => ['nullable', 'array', JsonApiRule::page()],
            'page.number' => ['sometimes', 'integer', 'min:1'],
            'page.size' => ['sometimes', 'integer', 'between:1,100'],
            'sort' => ['nullable', 'string', JsonApiRule::sort()],
        ];
    }
}
