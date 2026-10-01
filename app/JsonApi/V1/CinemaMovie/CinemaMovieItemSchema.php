<?php

declare(strict_types=1);

namespace App\JsonApi\V1\CinemaMovie;

use App\JsonApi\V1\DocumentSchema;

final class CinemaMovieItemSchema extends DocumentSchema
{
    protected static string $resourceType = 'cinema-movie-items';

    protected static array $attributeNames = [];
}
