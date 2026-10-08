<?php

declare(strict_types=1);

namespace App\Core\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Validates a generic User Space favorite without coupling Core to extensions. */
final class StoreFavoriteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'resource_type' => is_string($this->resource_type) ? strtolower(trim($this->resource_type)) : $this->resource_type,
            'resource_id' => is_string($this->resource_id) ? trim($this->resource_id) : $this->resource_id,
        ]);
    }

    public function rules(): array
    {
        return [
            'resource_type' => ['required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_.-]*$/'],
            'resource_id' => ['required', 'string', 'max:128', 'regex:/^[A-Za-z0-9:_-]+$/'],
            'metadata' => ['nullable', 'array', 'max:20'],
        ];
    }
}
