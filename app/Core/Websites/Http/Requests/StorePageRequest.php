<?php

declare(strict_types=1);

namespace App\Core\Websites\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates Website Engine page creation payloads.
 */
final class StorePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'status' => ['sometimes', 'in:draft,published,archived'],
            'template' => ['sometimes', 'string', 'max:255'],
            'seo' => ['sometimes', 'array'],
            'blocks' => ['sometimes', 'array'],
        ];
    }
}
