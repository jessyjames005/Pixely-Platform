<?php

/**
 * Validation for Music track create/update requests (`music-tracks`).
 *
 * Enforces the source-type and rights vocabularies and requires a YouTube video
 * id when the source type is `youtube`. The rights gate itself (unknown ⇒ not
 * public) is enforced at the model layer, so this request only validates shape.
 */

declare(strict_types=1);

namespace App\JsonApi\V1\Music;

use App\Extensions\Music\Models\Track;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

final class TrackRequest extends ResourceRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'trackNumber' => ['nullable', 'integer', 'min:1'],
            'discNumber' => ['nullable', 'integer', 'min:1'],
            'durationMs' => ['nullable', 'integer', 'min:0'],
            'sourceType' => ['sometimes', Rule::in(Track::SOURCE_TYPES)],
            'mime' => ['nullable', 'string', 'max:100'],
            'size' => ['nullable', 'integer', 'min:0'],
            'bitrate' => ['nullable', 'integer', 'min:0'],
            'sampleRate' => ['nullable', 'integer', 'min:0'],
            'channels' => ['nullable', 'integer', 'min:0'],
            'youtubeVideoId' => ['nullable', 'string', 'max:32'],
            'rightsStatus' => ['sometimes', Rule::in(Track::RIGHTS_STATUSES)],
            'rightsNote' => ['nullable', 'string', 'max:1000'],
            'visibility' => ['sometimes', Rule::in(Track::VISIBILITIES)],
            'status' => ['sometimes', Rule::in(Track::STATUSES)],
            'artist' => [JsonApiRule::toOne()],
            'album' => [JsonApiRule::toOne()],
            'genres' => [JsonApiRule::toMany()],
            'featuredArtists' => [JsonApiRule::toMany()],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $sourceType = $this->input('data.attributes.sourceType');

            if ($sourceType === 'youtube' && blank($this->input('data.attributes.youtubeVideoId'))) {
                $validator->errors()->add(
                    'youtubeVideoId',
                    'A YouTube video id is required when the source type is youtube.',
                );
            }
        });
    }
}
