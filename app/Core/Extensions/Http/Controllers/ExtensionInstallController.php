<?php

declare(strict_types=1);

namespace App\Core\Extensions\Http\Controllers;

use App\Core\Extensions\Audit\ExtensionAuditLogger;
use App\Core\Extensions\Installer\ExtensionInstaller;
use App\JsonApi\V1\DocumentId;
use App\JsonApi\V1\DocumentResource;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Server\Server;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Responses\DataResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Install, update, and uninstall extensions from an uploaded zip.
 *
 * Every route here requires system.extensions.install — a permission
 * deliberately never granted by default to any role, since this is
 * equivalent to arbitrary code execution on the server.
 */
#[Group('System Tooling', weight: 6)]
final class ExtensionInstallController
{
    public function __construct(
        private readonly ExtensionInstaller $installer,
        private readonly ExtensionAuditLogger $auditLogger,
    ) {
    }

    /**
     * Install a new extension from an uploaded zip package.
     */
    public function install(Request $request, Server $server): DataResponse
    {
        $validated = $request->validate([
            'package' => ['required', 'file', 'mimes:zip', 'max:20480'], // 20 MB
        ]);

        try {
            $result = $this->installer->install(
                $validated['package']->getRealPath(),
                $request->user()?->id,
            );
        } catch (\RuntimeException $exception) {
            throw JsonApiException::error([
                'status' => 422,
                'code' => 'INSTALL_FAILED',
                'title' => 'Unprocessable Entity',
                'detail' => 'The extension package could not be installed.',
            ], $exception);
        }

        return DataResponse::make($this->extensionResource($server, $result))->didCreate()->withServer('v1');
    }

    /**
     * Update an existing extension from an uploaded zip package.
     */
    public function update(Request $request, string $id, Server $server): DataResponse
    {
        $validated = $request->validate([
            'package' => ['required', 'file', 'mimes:zip', 'max:20480'],
        ]);

        try {
            $result = $this->installer->update(
                $validated['package']->getRealPath(),
                $this->extensionId($id),
                $request->user()?->id,
            );
        } catch (\RuntimeException $exception) {
            throw JsonApiException::error([
                'status' => 422,
                'code' => 'UPDATE_FAILED',
                'title' => 'Unprocessable Entity',
                'detail' => 'The extension package could not be updated.',
            ], $exception);
        }

        return DataResponse::make($this->extensionResource($server, $result))->withServer('v1');
    }

    /**
     * Uninstall an extension: removes its files from disk.
     *
     * Does not drop database tables or roll back migrations — see
     * ExtensionInstaller::uninstall() docblock.
     */
    public function destroy(string $id): Response
    {
        try {
            $this->installer->uninstall($id, request()->user()?->id);
        } catch (\RuntimeException $exception) {
            throw JsonApiException::error([
                'status' => 422,
                'code' => 'UNINSTALL_FAILED',
                'title' => 'Unprocessable Entity',
                'detail' => 'The extension could not be uninstalled.',
            ], $exception);
        }

        return response()->noContent();
    }

    /**
     * @param array{id: string, name: string, version: string} $result
     */
    private function extensionResource(Server $server, array $result): DocumentResource
    {
        return DocumentResource::make(
            $server->schemas()->schemaFor('extensions'),
            DocumentId::encode('extension', $result['id']),
            [
                'name' => $result['name'],
                'version' => $result['version'],
            ],
        );
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
}
