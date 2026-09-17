<?php

declare(strict_types=1);

namespace App\Core\Translations\Http\Controllers;

use App\Core\Api\Response\ApiResponse;
use App\Core\Translations\Services\TranslationRepository;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

/**
 * Public read-only endpoint serving the merged UI translation catalog
 * for a locale — every module's every group, nested as
 * {module: {group: {key: value}}}.
 *
 * Unlike the Translations extension's own API (gated behind
 * translations.strings.view/manage — that one's for the translation
 * *management* screen), this is what the frontend calls to render
 * translated text at all, so it must work before login too (the
 * login screen itself needs "Sign in", "Email", etc.).
 */
#[Group('Authentication', weight: 2)]
final class LocaleCatalogController
{
    public function __construct(
        private readonly TranslationRepository $repository,
    ) {
    }

    public function catalog(string $locale, ApiResponse $apiResponse): JsonResponse
    {
        $catalog = [];

        foreach ($this->repository->discoverModules() as $moduleId => $modulePath) {
            foreach ($this->repository->availableGroups($modulePath, $locale) as $group) {
                foreach ($this->repository->readGroup($modulePath, $locale, $group) as $key => $value) {
                    Arr::set($catalog, "{$moduleId}.{$group}.{$key}", $value);
                }
            }
        }

        return $apiResponse->response(data: $catalog);
    }
}
