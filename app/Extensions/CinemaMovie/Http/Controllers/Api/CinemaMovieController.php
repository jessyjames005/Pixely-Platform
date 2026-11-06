<?php

declare(strict_types=1);

namespace App\Extensions\CinemaMovie\Http\Controllers\Api;

use Dedoc\Scramble\Attributes\Group;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;

/** Handles JSON:API requests for the CinemaMovie catalogue. */
#[Group('CinemaMovie', weight: 10)]
final class CinemaMovieController extends JsonApiController
{
}
