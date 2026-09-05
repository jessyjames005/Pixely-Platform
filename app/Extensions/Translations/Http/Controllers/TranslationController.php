<?php

declare(strict_types=1);

namespace App\Extensions\Translations\Http\Controllers;

use App\Core\Api\Response\ApiCollectionResponse;
use App\Core\Api\Response\ApiResponse;
use App\Extensions\Translations\Services\TranslationRepository;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Browse and edit translation strings for Core and any extension
 * implementing ExtensionTranslatableInterface.
 */
#[Group('System Tooling', weight: 6)]
final class TranslationController
{
    public function __construct(
        private readonly TranslationRepository $repository,
    ) {
    }

    /**
     * List translatable modules and, per module, their available locales/groups.
     */
    public function modules(ApiCollectionResponse $apiResponse): JsonResponse
    {
        $modules = [];

        foreach ($this->repository->discoverModules() as $id => $path) {
            $modules[] = [
                'id' => $id,
                'locales' => $this->repository->availableLocales($path),
            ];
        }

        return $apiResponse->response(data: $modules, meta: ['total' => count($modules)]);
    }

    /**
     * List the translation groups (categories) available for a module's locale.
     */
    public function groups(Request $request, string $module, ApiCollectionResponse $apiResponse): JsonResponse
    {
        $modulePath = $this->resolveModulePath($module);
        $locale = $request->string('locale')->value() ?: 'en';

        $groups = $this->repository->availableGroups($modulePath, $locale);

        return $apiResponse->response(data: $groups, meta: ['total' => count($groups)]);
    }

    /**
     * Display translation entries for one group, target vs reference
     * locale, with completion percentage.
     */
    public function show(Request $request, string $module, string $group, ApiResponse $apiResponse): JsonResponse
    {
        $modulePath = $this->resolveModulePath($module);
        $targetLocale = $request->string('locale')->value() ?: 'fr';
        $referenceLocale = $request->string('reference')->value() ?: 'en';

        $result = $this->repository->compare($modulePath, $referenceLocale, $targetLocale, $group);

        return $apiResponse->response(data: [
            'module' => $module,
            'group' => $group,
            'locale' => $targetLocale,
            'reference' => $referenceLocale,
            'entries' => $result['entries'],
            'completion' => $result['completion'],
        ]);
    }

    /**
     * Save all translation entries for one group/locale in one request.
     */
    public function update(Request $request, string $module, string $group, ApiResponse $apiResponse): JsonResponse
    {
        $modulePath = $this->resolveModulePath($module);

        $validated = $request->validate([
            'locale' => ['required', 'string'],
            'translations' => ['required', 'array'],
            'translations.*' => ['nullable', 'string'],
        ]);

        $this->repository->writeGroup($modulePath, $validated['locale'], $group, $validated['translations']);

        return $apiResponse->response(data: ['saved' => true]);
    }

    private function resolveModulePath(string $module): string
    {
        $modules = $this->repository->discoverModules();

        if (! isset($modules[$module])) {
            abort(404, 'Translation module not found.');
        }

        return $modules[$module];
    }
}
