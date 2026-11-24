<?php

/**
 * Validation for Music album create/update requests (`music-albums`).
 */

declare(strict_types=1);

namespace App\JsonApi\V1\Music;

use App\Extensions\Music\Models\Album;
use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

final class AlbumRequest extends ResourceRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'releaseYear' => ['nullable', 'integer', 'between:1000,9999'],
            'type' => ['sometimes', Rule::in(['album', 'single', 'ep', 'compilation'])],
            'artist' => [JsonApiRule::toOne()],
            'cover' => [JsonApiRule::toOne()],
        ];
    }
}
