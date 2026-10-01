<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Settings;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

final class UserSettingRequest extends ResourceRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'locale' => ['sometimes', 'nullable', 'string', Rule::in(array_column(config('pixely.locales'), 'code'))],
            'theme' => ['sometimes', Rule::in(['system', 'light', 'dark'])],
            'density' => ['sometimes', Rule::in(['default', 'comfortable', 'compact'])],
            'email_notifications' => ['sometimes', 'boolean'],
        ];
    }
}