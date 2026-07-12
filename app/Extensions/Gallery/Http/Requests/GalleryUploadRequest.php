<?php

declare(strict_types=1);

namespace App\Extensions\Gallery\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate a gallery upload request.
 */
final class GalleryUploadRequest extends FormRequest
{
    /**
     * Determine whether the request is authorized.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'image' => ['required', 'image'],
        ];
    }
}
