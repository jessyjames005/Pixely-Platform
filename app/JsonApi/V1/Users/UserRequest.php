<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Users;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

final class UserRequest extends ResourceRequest
{
    public function rules(): array
    {
        return [
            'name' => [$this->isMethod('POST') ? 'required' : 'sometimes', 'string', 'max:255'],
            'email' => [
                $this->isMethod('POST') ? 'required' : 'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->model()?->getKey()),
            ],
            'password' => [$this->isMethod('POST') ? 'required' : 'sometimes', 'string', 'min:8'],
            'roles' => [JsonApiRule::toMany()],
        ];
    }
}
