<?php

declare(strict_types=1);

namespace App\Extensions\Gallery\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate a gallery update request.
 */
final class GalleryUpdateRequest extends FormRequest
{
    /**
     * Determine whether the request is authorized.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
        ];
    }
}
