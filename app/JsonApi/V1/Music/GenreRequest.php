<?php

/**
 * Validation for Music genre create/update requests (`music-genres`).
 */

declare(strict_types=1);

namespace App\JsonApi\V1\Music;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

final class GenreRequest extends ResourceRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'tracks' => [JsonApiRule::toMany()],
        ];
    }
}
