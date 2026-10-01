<?php

declare(strict_types=1);

namespace App\Extensions\Translations\Http\Controllers;

use App\Core\Translations\Services\TranslationRepository;
use App\JsonApi\V1\DocumentId;
use App\JsonApi\V1\DocumentResource;
use App\JsonApi\V1\Translations\TranslationGroupRequest;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use stdClass;
use LaravelJsonApi\Contracts\Server\Server;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\JsonApiService;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\NonEloquent\Pagination\EnumerablePagination;

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
    public function index(Request $request, Server $server, JsonApiService $jsonApi): DataResponse
    {
        return match ($jsonApi->route()->resourceType()) {
            'translation-modules' => $this->modules($server),
            'translation-groups' => $this->groups($request, $server),
            'translation-strings' => $this->strings($request, $server),
            default => abort(404),
        };
    }

    private function modules(Server $server): DataResponse
    {
        $modules = [];

        foreach ($this->repository->discoverModules() as $id => $path) {
            $modules[] = DocumentResource::make(
                $server->schemas()->schemaFor('translation-modules'),
                $id,
                ['locales' => $this->repository->availableLocales($path)],
            );
        }

        return DataResponse::make($modules)
            ->withServer('v1')
            ->withMeta(['total' => count($modules)]);
    }

    /**
     * List the translation groups (categories) available for a module's locale.
     */
    private function groups(Request $request, Server $server): DataResponse
    {
        $validated = $request->validate([
            'filter' => ['required', 'array:module,locale'],
            'filter.module' => ['required', 'string', 'max:255'],
            'filter.locale' => ['sometimes', 'string', 'max:35'],
            'page' => ['sometimes', 'array:number,size'],
            'page.number' => ['sometimes', 'integer', 'min:1'],
            'page.size' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);
        $module = $validated['filter']['module'];
        $locale = $validated['filter']['locale'] ?? 'en';
        $modulePath = $this->resolveModulePath($module);
        $groups = array_map(
            fn (string $group): DocumentResource => DocumentResource::make(
                $server->schemas()->schemaFor('translation-groups'),
                DocumentId::encode($module, $locale, $group),
                ['module' => $module, 'group' => $group, 'locale' => $locale],
            ),
            $this->repository->availableGroups($modulePath, $locale),
        );
        $data = $this->paginate($groups, $validated['page'] ?? null);

        return DataResponse::make($data)
            ->withServer('v1')
            ->withMeta(['total' => count($groups)]);
    }

    /**
     * Display translation entries for one group, target vs reference
     * locale, with completion percentage.
     */
    private function strings(Request $request, Server $server): DataResponse
    {
        $validated = $request->validate([
            'filter' => ['required', 'array:module,group,locale,reference'],
            'filter.module' => ['required', 'string', 'max:255'],
            'filter.group' => ['required', 'string', 'max:255'],
            'filter.locale' => ['sometimes', 'string', 'max:35'],
            'filter.reference' => ['sometimes', 'string', 'max:35'],
            'page' => ['sometimes', 'array:number,size'],
            'page.number' => ['sometimes', 'integer', 'min:1'],
            'page.size' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);
        $module = $validated['filter']['module'];
        $group = $validated['filter']['group'];
        $targetLocale = $validated['filter']['locale'] ?? 'fr';
        $referenceLocale = $validated['filter']['reference'] ?? 'en';
        $modulePath = $this->resolveModulePath($module);
        $result = $this->repository->compare($modulePath, $referenceLocale, $targetLocale, $group);
        $strings = array_map(
            fn (array $entry): DocumentResource => DocumentResource::make(
                $server->schemas()->schemaFor('translation-strings'),
                DocumentId::encode($module, $group, $targetLocale, $entry['key']),
                $entry,
            ),
            $result['entries'],
        );
        $data = $this->paginate($strings, $validated['page'] ?? null);

        return DataResponse::make($data)
            ->withServer('v1')
            ->withMeta([
                'module' => $module,
                'group' => $group,
                'locale' => $targetLocale,
                'reference' => $referenceLocale,
                'completion' => $result['completion'],
            ]);
    }

    /**
     * Save all translation entries for one group/locale in one request.
     */
    public function update(
        TranslationGroupRequest $request,
        stdClass $translationGroup,
        Server $server,
    ): DataResponse {
        $translationGroupId = (string) $translationGroup->id;
        $parts = DocumentId::decode($translationGroupId, 3);
        abort_if($parts === null, 404);
        [$module, $locale, $group] = $parts;
        $modulePath = $this->resolveModulePath($module);
        $validated = $request->validated();

        if ($validated['locale'] !== $locale) {
            throw JsonApiException::error([
                'status' => 409,
                'code' => 'RESOURCE_ID_MISMATCH',
                'title' => 'Resource ID mismatch',
                'detail' => 'The locale attribute must match the translation group resource ID.',
                'source' => ['pointer' => '/data/attributes/locale'],
            ]);
        }

        // Validated manually rather than via a 'translations.*' wildcard
        // rule: Laravel's dot-notation validator misinterprets literal dots
        // inside our translation keys (e.g. 'action.upload') as nesting
        // levels, silently dropping those entries from $validated.
        foreach ($validated['translations'] as $value) {
            if ($value !== null && ! is_string($value)) {
                throw JsonApiException::error([
                    'status' => 422,
                    'code' => 'INVALID_TRANSLATION_VALUE',
                    'title' => 'Invalid translation value',
                    'detail' => 'Each translation value must be a string or null.',
                    'source' => ['pointer' => '/data/attributes/translations'],
                ]);
            }
        }

        $this->repository->writeGroup($modulePath, $locale, $group, $validated['translations']);

        return DataResponse::make(DocumentResource::make(
            $server->schemas()->schemaFor('translation-groups'),
            $translationGroupId,
            [
                'module' => $module,
                'group' => $group,
                'locale' => $locale,
                'translations' => $validated['translations'],
                'saved' => true,
            ],
        ))->withServer('v1');
    }

    private function paginate(array $resources, ?array $page): iterable
    {
        if ($page === null) {
            return $resources;
        }

        return EnumerablePagination::make()
            ->withDefaultPerPage(50)
            ->paginate($resources, $page);
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
