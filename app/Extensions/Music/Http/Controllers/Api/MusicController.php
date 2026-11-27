<?php

/**
 * Handles Music administration API requests.
 *
 * JSON:API CRUD for the catalogue (tracks, albums, artists, genres) is provided
 * by the base JsonApiController; the matching schemas live in
 * App\JsonApi\V1\Music and the resources are registered in API/routes.php with
 * per-action permission middleware. The rights gate is enforced at the model
 * layer (defence-in-depth) so track writes can never persist a public + unknown
 * track, regardless of the entry point.
 */

declare(strict_types=1);

namespace App\Extensions\Music\Http\Controllers\Api;

use Dedoc\Scramble\Attributes\Group;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;

#[Group('Music', weight: 10)]
final class MusicController extends JsonApiController
{
}
