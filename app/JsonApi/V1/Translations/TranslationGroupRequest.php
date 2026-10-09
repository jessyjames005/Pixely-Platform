<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Translations;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

final class TranslationGroupRequest extends ResourceRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'locale' => ['required', 'string', 'max:35'],
            'translations' => ['required', 'array'],
        ];
    }
}
