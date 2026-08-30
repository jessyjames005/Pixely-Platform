<?php

declare(strict_types=1);

namespace App\Core\Extensions\Audit;

use Illuminate\Support\Facades\Auth;

/**
 * Records extension lifecycle actions to the audit trail.
 */
final class ExtensionAuditLogger
{
    /**
     * @param array<string, mixed>|null $details
     */
    public function log(string $extensionId, string $action, ?string $version = null, ?array $details = null): void
    {
        ExtensionAuditLog::create([
            'user_id' => Auth::id(),
            'extension_id' => $extensionId,
            'action' => $action,
            'version' => $version,
            'details' => $details,
        ]);
    }
}
