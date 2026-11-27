<?php

/**
 * Validation for Music artist create/update requests (`music-artists`).
 */

declare(strict_types=1);

namespace App\JsonApi\V1\Music;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

final class ArtistRequest extends ResourceRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sortName' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'image' => [JsonApiRule::toOne()],
        ];
    }
}
