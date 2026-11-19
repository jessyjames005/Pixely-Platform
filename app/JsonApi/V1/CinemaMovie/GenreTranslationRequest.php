<?php

declare(strict_types=1);

namespace App\JsonApi\V1\CinemaMovie;

use App\Extensions\CinemaMovie\Services\CinemaMovieSettings;
use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

final class GenreTranslationRequest extends ResourceRequest
{
    public function rules(): array
    {
        $locales = app(CinemaMovieSettings::class)->supportedLocales();

        return [
            'locale' => ['required', 'string', Rule::in($locales)],
            'name' => ['required', 'string', 'max:100'],
            'genre' => ['required', JsonApiRule::toOne()],
        ];
    }
}
