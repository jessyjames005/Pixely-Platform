<?php

declare(strict_types=1);

namespace App\Core\Extensions\Http\Controllers;

use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Audit\ExtensionAuditLogger;
use App\Core\Extensions\Configuration\ExtensionConfigurableInterface;
use App\Core\Extensions\Configuration\ExtensionConfigurationRepositoryInterface;
use App\Core\Extensions\Manager\ExtensionManager;
use App\Core\Extensions\Permissions\ExtensionPermissionSynchronizer;
use App\JsonApi\V1\DocumentId;
use App\JsonApi\V1\DocumentResource;
use Illuminate\Http\Request;
use Dedoc\Scramble\Attributes\Group;
use LaravelJsonApi\Contracts\Server\Server;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Responses\DataResponse;

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
    public function index(Server $server): DataResponse
    {
        $extensions = array_map(
            fn (ExtensionInterface $extension) => $this->extensionResource($server, $extension),
            $this->manager->all(),
        );

        return DataResponse::make(array_values($extensions))->withServer('v1');
    }

    /**
     * Display a single extension's details.
     */
    public function show(string $id, Server $server): DataResponse
    {
        $extension = $this->findExtension($id);

        return DataResponse::make($this->extensionResource($server, $extension))->withServer('v1');
    }

    /**
     * Enable an extension.
     */
    public function enable(string $id, Server $server): DataResponse
    {
        $extensionId = $this->extensionId($id);
        $this->findExtension($id);

        try {
            $this->manager->enable($extensionId);
        } catch (\Throwable $exception) {
            throw JsonApiException::error([
                'status' => 422,
                'code' => 'DEPENDENCY_ERROR',
                'title' => 'Unprocessable Entity',
                'detail' => 'The extension cannot be enabled because a dependency requirement was not met.',
            ], $exception);
        }

        $extension = $this->manager->all()[$extensionId];
        $this->permissionSynchronizer->sync($extension);

        $this->auditLogger->log($extensionId, 'enable');

        return DataResponse::make($this->extensionResource($server, $extension))->withServer('v1');
    }

    /**
     * Disable an extension.
     */
    public function disable(string $id, Server $server): DataResponse
    {
        $extensionId = $this->extensionId($id);
        $this->findExtension($id);

        $this->manager->disable($extensionId);
        $this->auditLogger->log($extensionId, 'disable');

        return DataResponse::make(
            $this->extensionResource($server, $this->manager->all()[$extensionId]),
        )->withServer('v1');
    }

    /**
     * Display an extension's configuration: its declared defaults (if
     * any) alongside the current effective values (defaults merged with
     * any stored overrides) — enough for the frontend to render a form
     * without needing to already know the extension's config shape.
     */
    public function showConfig(string $id, Server $server): DataResponse
    {
        $extensionId = $this->extensionId($id);
        $extension = $this->findExtension($id);

        $defaults = $extension instanceof ExtensionConfigurableInterface
            ? $extension->defaultConfiguration()
            : [];
        $overrides = $this->configRepository->load($extensionId);

        return DataResponse::make($this->configurationResource(
            $server,
            $extensionId,
            $defaults,
            [...$defaults, ...$overrides],
        ))
            ->withServer('v1');
    }

    /**
     * Update an extension's configuration overrides.
     */
    public function updateConfig(Request $request, string $id, Server $server): DataResponse
    {
        $extensionId = $this->extensionId($id);
        $this->findExtension($id);
        $configurationId = DocumentId::encode('extension-configuration', $extensionId);

        $validated = $request->validate([
            'data' => ['required', 'array'],
            'data.type' => ['required', 'in:extension-configurations'],
            'data.id' => ['required', 'string', 'in:' . $configurationId],
            'data.attributes' => ['required', 'array:values', 'min:1'],
            'data.attributes.values' => ['required', 'array'],
        ]);
        $configuration = $validated['data']['attributes']['values'];

        $this->configRepository->save($extensionId, $configuration);

        $extension = $this->manager->all()[$extensionId];
        $defaults = $extension instanceof ExtensionConfigurableInterface
            ? $extension->defaultConfiguration()
            : [];

        return DataResponse::make($this->configurationResource(
            $server,
            $extensionId,
            $defaults,
            [...$defaults, ...$this->configRepository->load($extensionId)],
        ))->withServer('v1');
    }

    private function findExtension(string $opaqueId): ExtensionInterface
    {
        $extensionId = $this->extensionId($opaqueId);
        $extension = $this->manager->all()[$extensionId] ?? null;

        if (! $extension instanceof ExtensionInterface) {
            throw JsonApiException::error([
                'status' => 404,
                'code' => 'RESOURCE_NOT_FOUND',
                'title' => 'Not Found',
                'detail' => 'The requested extension was not found.',
            ]);
        }

        return $extension;
    }

    private function extensionId(string $opaqueId): string
    {
        $parts = DocumentId::decode($opaqueId, 2);

        if ($parts === null || $parts[0] !== 'extension') {
            throw JsonApiException::error([
                'status' => 404,
                'code' => 'RESOURCE_NOT_FOUND',
                'title' => 'Not Found',
                'detail' => 'The requested extension was not found.',
            ]);
        }

        return $parts[1];
    }

    private function extensionResource(Server $server, ExtensionInterface $extension): DocumentResource
    {
        $manifest = $extension->manifest();
        $state = $this->manager->findState($manifest->id);

        return DocumentResource::make(
            $server->schemas()->schemaFor('extensions'),
            DocumentId::encode('extension', $manifest->id),
            [
                'name' => $manifest->name,
                'version' => $manifest->version,
                'dependencies' => $manifest->dependencies,
                'enabled' => $state?->isEnabled() ?? false,
            ],
        );
    }

    /**
     * @param array<string, mixed> $defaults
     * @param array<string, mixed> $values
     */
    private function configurationResource(
        Server $server,
        string $extensionId,
        array $defaults,
        array $values,
    ): DocumentResource {
        return DocumentResource::make(
            $server->schemas()->schemaFor('extension-configurations'),
            DocumentId::encode('extension-configuration', $extensionId),
            [
                'defaults' => $this->publicConfiguration($defaults),
                'values' => $this->publicConfiguration($values),
            ],
        );
    }

    /**
     * @param array<string, mixed> $configuration
     * @return array<string, mixed>
     */
    private function publicConfiguration(array $configuration): array
    {
        $public = [];

        foreach ($configuration as $key => $value) {
            $sensitiveKey = preg_match(
                '/secret|password|passphrase|token|credential|authorization|private|'
                . 'api[_-]?key|access[_-]?key|path|directory|filesystem|file[_-]?name/i',
                (string) $key,
            ) === 1;

            if ($sensitiveKey) {
                continue;
            }

            $public[$key] = is_array($value) ? $this->publicConfiguration($value) : $value;
        }

        return $public;
    }
}
