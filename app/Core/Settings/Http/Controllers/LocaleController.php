<?php

declare(strict_types=1);

namespace App\Core\Settings\Http\Controllers;

use App\JsonApi\V1\DocumentResource;
use Dedoc\Scramble\Attributes\Group;
use LaravelJsonApi\Contracts\Server\Server;
use LaravelJsonApi\Core\Responses\DataResponse;

/**
 * Exposes the list of locales available across the platform.
 */
#[Group('Settings & Localization', weight: 5)]
final class LocaleController
{
    public function index(Server $server): DataResponse
    {
        $resources = array_map(
            fn (array $locale): DocumentResource => DocumentResource::make(
                $server->schemas()->schemaFor('locales'),
                $locale['code'],
                $locale,
            ),
            config('pixely.locales'),
        );

        return DataResponse::make($resources)
            ->withServer('v1')
            ->withMeta(['default' => config('pixely.default_locale')]);
    }
}
