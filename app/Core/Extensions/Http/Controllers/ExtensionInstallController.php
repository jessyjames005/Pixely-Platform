<?php

declare(strict_types=1);

namespace App\Core\Extensions\Http\Controllers;

use App\Core\Api\Response\ApiResponse;
use App\Core\Extensions\Audit\ExtensionAuditLogger;
use App\Core\Extensions\Installer\ExtensionInstaller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
    public function install(Request $request, ApiResponse $apiResponse): JsonResponse
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
            return response()->json(
                ['error' => ['code' => 'INSTALL_FAILED', 'message' => $exception->getMessage()]],
                422,
            );
        }

        return $apiResponse->response(data: $result, status: 201);
    }

    /**
     * Update an existing extension from an uploaded zip package.
     */
    public function update(Request $request, string $id, ApiResponse $apiResponse): JsonResponse
    {
        $validated = $request->validate([
            'package' => ['required', 'file', 'mimes:zip', 'max:20480'],
        ]);

        try {
            $result = $this->installer->update(
                $validated['package']->getRealPath(),
                $id,
                $request->user()?->id,
            );
        } catch (\RuntimeException $exception) {
            return response()->json(
                ['error' => ['code' => 'UPDATE_FAILED', 'message' => $exception->getMessage()]],
                422,
            );
        }

        return $apiResponse->response(data: $result);
    }

    /**
     * Uninstall an extension: removes its files from disk.
     *
     * Does not drop database tables or roll back migrations — see
     * ExtensionInstaller::uninstall() docblock.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->installer->uninstall($id, request()->user()?->id);
        } catch (\RuntimeException $exception) {
            return response()->json(
                ['error' => ['code' => 'UNINSTALL_FAILED', 'message' => $exception->getMessage()]],
                422,
            );
        }

        return response()->json(status: 204);
    }
}
