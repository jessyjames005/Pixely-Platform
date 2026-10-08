<?php

declare(strict_types=1);

namespace App\Core\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Validates a history event; the server owns the event timestamp. */
final class StoreHistoryRequest extends FormRequest
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
            'action' => is_string($this->action) ? strtolower(trim($this->action)) : $this->action,
        ]);
    }

    public function rules(): array
    {
        return [
            'resource_type' => ['required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_.-]*$/'],
            'resource_id' => ['required', 'string', 'max:128', 'regex:/^[A-Za-z0-9:_-]+$/'],
            'action' => ['required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_.-]*$/'],
            'metadata' => ['nullable', 'array', 'max:20'],
        ];
    }
}
