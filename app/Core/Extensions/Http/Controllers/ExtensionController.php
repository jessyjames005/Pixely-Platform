<?php

declare(strict_types=1);

namespace App\Core\Extensions\Http\Controllers;

use App\Core\Api\Response\ApiCollectionResponse;
use App\Core\Api\Response\ApiResponse;
use App\Core\Extensions\Audit\ExtensionAuditLogger;
use App\Core\Extensions\Configuration\ExtensionConfigurableInterface;
use App\Core\Extensions\Configuration\ExtensionConfigurationRepositoryInterface;
use App\Core\Extensions\Manager\ExtensionManager;
use App\Core\Extensions\Permissions\ExtensionPermissionSynchronizer;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $configuration = $request->validate(['*' => ['sometimes']]) ?: $request->all();

        $this->configRepository->save($id, $configuration);

        $extension = $this->manager->all()[$id];
        $defaults = $extension instanceof ExtensionConfigurableInterface
            ? $extension->defaultConfiguration()
            : [];

        return $apiResponse->response(data: [
            'defaults' => $defaults,
            'values' => [...$defaults, ...$this->configRepository->load($id)],
        ]);
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
        ];
    }
}
