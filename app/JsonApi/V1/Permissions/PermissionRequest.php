<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Permissions;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

final class PermissionRequest extends ResourceRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                $this->isMethod('POST') ? 'required' : 'sometimes',
                'string',
                'max:255',
                Rule::unique('permissions', 'name')
                    ->where('guard_name', 'web')
                    ->ignore($this->model()?->getKey()),
            ],
            'isCore' => ['sometimes', 'boolean'],
        ];
    }
}
