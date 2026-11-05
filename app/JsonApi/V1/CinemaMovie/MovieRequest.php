<?php

declare(strict_types=1);

namespace App\JsonApi\V1\CinemaMovie;

use App\Extensions\CinemaMovie\Models\Movie;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

final class MovieRequest extends ResourceRequest
{
    public function rules(): array
    {
        $movieId = $this->model() instanceof Movie ? $this->model()->getKey() : null;

        return [
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('cinema_movies', 'slug')->ignore($movieId)],
            'originalTitle' => ['nullable', 'string', 'max:255'],
            'originalLanguage' => ['nullable', 'string', 'max:10'],
            'releaseDate' => ['nullable', 'date'],
            'runtimeMinutes' => ['nullable', 'integer', 'between:1,1000'],
            'ageRating' => ['nullable', 'string', 'max:10'],
            'productionStatus' => ['sometimes', Rule::in(Movie::PRODUCTION_STATUSES)],
            'publicationStatus' => ['sometimes', Rule::in(Movie::PUBLICATION_STATUSES)],
            'budget' => ['nullable', 'integer', 'min:0'],
            'revenue' => ['nullable', 'integer', 'min:0'],
            'voteAverage' => ['nullable', 'numeric', 'between:0,10'],
            'voteCount' => ['sometimes', 'integer', 'min:0'],
            'genres' => [JsonApiRule::toMany()],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $publicationStatus = $this->input(
                'data.attributes.publicationStatus',
                $this->model()?->publication_status,
            );

            if ($publicationStatus !== 'published') {
                return;
            }

            $defaultLocale = app(\App\Extensions\CinemaMovie\Services\CinemaMovieSettings::class)
                ->supportedLocales()[0] ?? null;
            $movie = $this->model();

            if (
                $defaultLocale === null
                || $movie === null
                || ! $movie->translations()->where('locale', $defaultLocale)->whereNotNull('title')->exists()
            ) {
                $validator->errors()->add(
                    'publicationStatus',
                    'A movie needs a title in the default locale before it can be published.',
                );
            }
        });
    }
}
