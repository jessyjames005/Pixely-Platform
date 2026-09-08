<?php

declare(strict_types=1);

namespace App\Core\Settings\Http\Controllers;

use App\Core\Api\Response\ApiCollectionResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

/**
 * Exposes the list of locales available across the platform.
 */
#[Group('Settings & Localization', weight: 5)]
final class LocaleController
{
    public function index(ApiCollectionResponse $apiResponse): JsonResponse
    {
        $locales = config('pixely.locales');

        return $apiResponse->response(
            data: $locales,
            meta: ['default' => config('pixely.default_locale')],
        );
    }
}
