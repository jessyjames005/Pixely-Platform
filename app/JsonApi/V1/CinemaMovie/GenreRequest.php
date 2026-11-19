<?php

declare(strict_types=1);

namespace App\JsonApi\V1\CinemaMovie;

use App\Extensions\CinemaMovie\Models\Genre;
use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

final class GenreRequest extends ResourceRequest
{
    public function rules(): array
    {
        $genreId = $this->model() instanceof Genre ? $this->model()->getKey() : null;

        return [
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('cinema_genres', 'slug')->ignore($genreId)],
        ];
    }
}
