<?php

declare(strict_types=1);

namespace App\Core\Websites\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates Website Engine page update payloads.
 */
final class UpdatePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slug' => ['sometimes', 'nullable', 'string', 'max:255'],
            'title' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'in:draft,published,archived'],
            'template' => ['sometimes', 'string', 'max:255'],
            'seo' => ['sometimes', 'array'],
            'blocks' => ['sometimes', 'array'],
        ];
    }
}
