<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Settings;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

final class PlatformSettingRequest extends ResourceRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'site_name' => ['sometimes', 'string', 'max:255'],
            'locale' => ['sometimes', 'string', Rule::in(array_column(config('pixely.locales'), 'code'))],
        ];
    }
}
