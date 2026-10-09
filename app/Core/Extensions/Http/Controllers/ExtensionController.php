<?php

declare(strict_types=1);

namespace App\Core\Extensions\Http\Controllers;

use App\Core\Api\Response\ApiCollectionResponse;
use App\Core\Api\Response\ApiResponse;
use App\Core\Extensions\Audit\ExtensionAuditLogger;
use App\Core\Extensions\Capabilities\Contracts\ExtensionNavigationInterface;
use App\Core\Extensions\Capabilities\Contracts\ExtensionSettingsInterface;
use App\Core\Extensions\Capabilities\Registry\ExtensionBlockRegistry;
use App\Core\Extensions\Capabilities\Registry\ExtensionCapabilityRegistry;
use App\Core\Extensions\Configuration\ExtensionConfigurableInterface;
use App\Core\Extensions\Configuration\ExtensionConfigurationRepositoryInterface;
use App\Core\Extensions\Manager\ExtensionManager;
use App\Core\Extensions\Permissions\ExtensionPermissionSynchronizer;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Read/lifecycle (non-destructive) extension management API.
 *
 * Install, update and uninstall live in ExtensionInstallController,
 * behind a separate, more restrictive permission.
 */
#[Group('System Tooling', weight: 6)]
final class ExtensionController
{
    public function __construct(
        private readonly ExtensionManager $manager,
        private readonly ExtensionConfigurationRepositoryInterface $configRepository,
        private readonly ExtensionAuditLogger $auditLogger,
        private readonly ExtensionPermissionSynchronizer $permissionSynchronizer,
        private readonly ExtensionCapabilityRegistry $capabilityRegistry,
        private readonly ExtensionBlockRegistry $blockRegistry,
    ) {
    }

    /**
     * List all registered extensions with their current state.
     */
    public function index(ApiCollectionResponse $apiResponse): JsonResponse
    {
        $extensions = array_map(
            fn ($extension) => $this->summarize($extension),
            $this->manager->all(),
        );

        return $apiResponse->response(
            data: array_values($extensions),
            meta: ['total' => count($extensions)],
        );
    }

    /**
     * Return navigation contributed by enabled extensions for the admin surface.
     *
     * Permissions are filtered server-side so users never receive navigation
     * entries they are not authorized to use.
     */
    public function navigation(Request $request, ApiCollectionResponse $apiResponse): JsonResponse
    {
        $items = [];

        foreach ($this->manager->enabled() as $extension) {
            if (! in_array('admin', $extension->manifest()->surfaces, true)) {
                continue;
            }

            if (! $extension instanceof ExtensionNavigationInterface) {
                continue;
            }

            foreach ($extension->navigation() as $item) {
                $items[] = $this->filterNavigationItem($item, $request);
            }
        }

        $items = array_values(array_filter(
            $items,
            static fn (mixed $item): bool => is_array($item),
        ));

        usort(
            $items,
            static fn (array $left, array $right): int => (int) ($left['order'] ?? 1000) <=> (int) ($right['order'] ?? 1000),
        );

        return $apiResponse->response(
            data: $items,
            meta: ['total' => count($items)],
        );
    }

    /**
     * Return the validated block declarations contributed by enabled extensions.
     */
    public function blocks(ApiCollectionResponse $apiResponse): JsonResponse
    {
        $blocks = [];

        foreach ($this->manager->enabled() as $extension) {
            foreach ($this->blockRegistry->for($extension) as $block) {
                $blocks[] = $block;
            }
        }

        return $apiResponse->response(
            data: $blocks,
            meta: ['total' => count($blocks)],
        );
    }

    /**
     * Return the typed settings schema declared by an extension.
     */
    public function settingsSchema(string $id, ApiResponse $apiResponse): JsonResponse
    {
        if (! $this->manager->has($id)) {
            abort(404, 'Extension not found.');
        }

        $extension = $this->manager->all()[$id];
        $schema = $extension instanceof ExtensionSettingsInterface
            ? $extension->settings()
            : [];

        return $apiResponse->response(data: [
            'extension_id' => $id,
            'schema' => $schema,
        ]);
    }

    /**
     * Display a single extension's details.
     */
    public function show(string $id, ApiResponse $apiResponse): JsonResponse
    {
        if (! $this->manager->has($id)) {
            abort(404, 'Extension not found.');
        }

        $extension = $this->manager->all()[$id];

        return $apiResponse->response(data: [
            ...$this->summarize($extension),
            'path' => $extension->manifest()->path,
            'providers' => $extension->providers(),
        ]);
    }

    /**
     * Enable an extension.
     */
    public function enable(string $id, ApiResponse $apiResponse): JsonResponse
    {
        if (! $this->manager->has($id)) {
            abort(404, 'Extension not found.');
        }

        try {
            $this->manager->enable($id);
        } catch (\Throwable $exception) {
            return response()->json(
                ['error' => ['code' => 'DEPENDENCY_ERROR', 'message' => $exception->getMessage()]],
                422,
            );
        }

        $this->permissionSynchronizer->sync($this->manager->all()[$id]);

        $this->auditLogger->log($id, 'enable');

        return $apiResponse->response(data: $this->summarize($this->manager->all()[$id]));
    }

    /**
     * Disable an extension.
     */
    public function disable(string $id, ApiResponse $apiResponse): JsonResponse
    {
        if (! $this->manager->has($id)) {
            abort(404, 'Extension not found.');
        }

        $this->manager->disable($id);
        $this->auditLogger->log($id, 'disable');

        return $apiResponse->response(data: $this->summarize($this->manager->all()[$id]));
    }

    /**
     * Display an extension's configuration: its declared defaults (if
     * any) alongside the current effective values (defaults merged with
     * any stored overrides) — enough for the frontend to render a form
     * without needing to already know the extension's config shape.
     */
    public function showConfig(string $id, ApiResponse $apiResponse): JsonResponse
    {
        if (! $this->manager->has($id)) {
            abort(404, 'Extension not found.');
        }

        $extension = $this->manager->all()[$id];
        $defaults = $extension instanceof ExtensionConfigurableInterface
            ? $extension->defaultConfiguration()
            : [];
        $overrides = $this->configRepository->load($id);

        return $apiResponse->response(data: [
            'defaults' => $defaults,
            'values' => [...$defaults, ...$overrides],
        ]);
    }

    /**
     * Update an extension's configuration overrides.
     */
    public function updateConfig(Request $request, string $id, ApiResponse $apiResponse): JsonResponse
    {
        if (! $this->manager->has($id)) {
            abort(404, 'Extension not found.');
        }

        $extension = $this->manager->all()[$id];
        $configuration = $request->input('values', $request->all());

        if (! is_array($configuration)) {
            abort(422, 'Extension configuration must be an object.');
        }

        // When an extension declares a settings schema, accept only declared
        // keys and validate each value against that schema before persistence.
        // Extensions without the new contract keep the legacy configuration path.
        if ($extension instanceof ExtensionSettingsInterface) {
            $schema = $extension->settings();
            $rules = [];

            foreach ($configuration as $key => $_value) {
                if (! is_string($key) || ! array_key_exists($key, $schema)) {
                    abort(422, 'Unknown extension setting: '.(string) $key);
                }
            }

            foreach ($schema as $key => $definition) {
                $fieldRules = (($definition['required'] ?? false) === true) ? ['required'] : ['sometimes'];

                $type = $definition['type'] ?? 'string';
                $fieldRules[] = match ($type) {
                    'integer' => 'integer',
                    'number' => 'numeric',
                    'boolean' => 'boolean',
                    'string[]' => 'array',
                    'array' => 'array',
                    'object' => 'array',
                    default => 'string',
                };

                if (isset($definition['min']) && in_array($type, ['integer', 'number', 'string'], true)) {
                    $fieldRules[] = ($type === 'string' ? 'min:' : 'min:').$definition['min'];
                }
                if (isset($definition['max']) && in_array($type, ['integer', 'number', 'string'], true)) {
                    $fieldRules[] = 'max:'.$definition['max'];
                }
                if (isset($definition['options']) && is_array($definition['options'])) {
                    $fieldRules[] = Rule::in($definition['options']);
                }
                if ($type === 'string[]') {
                    $rules[$key.'.*'] = ['string'];
                }
                $rules[$key] = $fieldRules;
            }

            $configuration = Validator::make($configuration, $rules)->validate();
        }

        $this->configRepository->save($id, $configuration);

        $defaults = $extension instanceof ExtensionConfigurableInterface
            ? $extension->defaultConfiguration()
            : [];

        return $apiResponse->response(data: [
            'defaults' => $defaults,
            'values' => [...$defaults, ...$this->configRepository->load($id)],
        ]);
    }

    /**
     * Filter one navigation item recursively against the authenticated user.
     *
     * @param array<string, mixed> $item
     * @return array<string, mixed>|null
     */
    private function filterNavigationItem(array $item, Request $request): ?array
    {
        $permission = $item['permission'] ?? null;

        if (is_string($permission) && ! $request->user()?->can($permission)) {
            return null;
        }

        if (isset($item['children']) && is_array($item['children'])) {
            $children = [];

            foreach ($item['children'] as $child) {
                if (! is_array($child)) {
                    continue;
                }

                $filtered = $this->filterNavigationItem($child, $request);
                if ($filtered !== null) {
                    $children[] = $filtered;
                }
            }

            if ($children === []) {
                return null;
            }

            $item['children'] = $children;
        }

        return $item;
    }

    /**
     * @return array<string, mixed>
     */
    private function summarize($extension): array
    {
        $manifest = $extension->manifest();
        $state = $this->manager->findState($manifest->id);

        return [
            'id' => $manifest->id,
            'name' => $manifest->name,
            'version' => $manifest->version,
            'dependencies' => $manifest->dependencies,
            'enabled' => $state?->isEnabled() ?? false,
            'capabilities' => array_map(
                static fn ($capability): string => $capability->value,
                $this->capabilityRegistry->for($extension),
            ),
        ];
    }
}
