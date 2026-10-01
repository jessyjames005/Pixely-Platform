<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Photos;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

final class PhotoRequest extends ResourceRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:1', 'max:255'],
            'filename' => [
                'required',
                'string',
                'regex:/\Agallery\/[A-Za-z0-9_-]+\.[A-Za-z0-9]+\z/',
            ],
        ];
    }
}
