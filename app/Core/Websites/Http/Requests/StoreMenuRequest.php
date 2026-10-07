<?php

declare(strict_types=1);

namespace App\Core\Websites\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates Website Engine menu payloads.
 */
final class StoreMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['sometimes', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255'],
            'items' => ['sometimes', 'array'],
            'items.*.id' => ['sometimes', 'string', 'max:255'],
            'items.*.type' => ['required', 'in:page,extension,external'],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.targetUrl' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'items.*.pageId' => ['sometimes', 'nullable', 'string', 'max:255'],
            'items.*.extensionId' => ['sometimes', 'nullable', 'string', 'max:255'],
            'items.*.slug' => ['sometimes', 'nullable', 'string', 'max:255'],
            'items.*.sortOrder' => ['sometimes', 'integer', 'min:0'],
            'items.*.active' => ['sometimes', 'boolean'],
        ];
    }
}
