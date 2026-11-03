<?php

declare(strict_types=1);

namespace App\Core\Extensions\Audit;

use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Records extension lifecycle actions to the audit trail.
 */
final class ExtensionAuditLogger
{
    /**
     * @param array<string, mixed>|null $details
     */
    public function log(
        string $extensionId,
        string $action,
        ?string $version = null,
        ?array $details = null,
        ?int $userId = null,
    ): void {
        ExtensionAuditLog::create([
            'user_id' => $userId ?? Auth::id(),
            'extension_id' => $extensionId,
            'action' => $action,
            'version' => $version,
            'details' => $details,
        ]);
    }

    /**
     * Persist an audit event without allowing an audit-store outage to replace
     * the result of the operation being recorded.
     *
     * @param array<string, mixed>|null $details
     */
    public function logSafely(
        string $extensionId,
        string $action,
        ?string $version = null,
        ?array $details = null,
        ?int $userId = null,
    ): bool {
        try {
            $this->log($extensionId, $action, $version, $details, $userId);
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }

        return true;
    }
}
