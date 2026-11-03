<?php

declare(strict_types=1);

namespace App\Core\Extensions\Http\Controllers;

use App\Core\Extensions\Audit\ExtensionAuditLog;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Read-only, filtered access to extension lifecycle audit events.
 */
#[Group('System Tooling', weight: 6)]
final class ExtensionAuditLogController
{
    private const ACTIONS = [
        'install',
        'update',
        'uninstall',
        'enable',
        'disable',
        'upgrade_step',
        'download',
        'preflight',
    ];

    /**
     * List audit events without exposing untrusted package-provided details.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'extension_id' => ['sometimes', 'string', 'regex:/^[A-Za-z0-9][A-Za-z0-9._-]{0,127}$/'],
            'action' => ['sometimes', 'string', Rule::in(self::ACTIONS)],
            'outcome' => ['sometimes', 'string', Rule::in(['success', 'failure'])],
        ]);

        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 15);
        $query = ExtensionAuditLog::query();

        if (isset($filters['extension_id'])) {
            $query->where('extension_id', $filters['extension_id']);
        }

        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (isset($filters['outcome'])) {
            $query->where('details->outcome', $filters['outcome']);
        }

        $paginator = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage, [
                'id',
                'user_id',
                'extension_id',
                'action',
                'version',
                'details',
                'created_at',
            ], 'page', $page);

        $data = $paginator->getCollection()
            ->map(function (ExtensionAuditLog $event): array {
                $details = $this->safeDetails($event->details);

                return [
                    'type' => 'extension-audit-logs',
                    'id' => (string) $event->id,
                    'attributes' => [
                        'user_id' => $event->user_id,
                        'extension_id' => $event->extension_id,
                        'action' => $event->action,
                        'version' => $event->version,
                        'outcome' => $details['outcome'] ?? null,
                        'stage' => $details['stage'] ?? null,
                        'details' => $details,
                        'created_at' => $event->created_at?->toIso8601String(),
                    ],
                ];
            })
            ->all();

        $queryParameters = array_intersect_key(
            $filters,
            array_flip(['per_page', 'extension_id', 'action', 'outcome']),
        );
        $pageUrl = static function (?int $targetPage) use ($request, $queryParameters): ?string {
            if ($targetPage === null) {
                return null;
            }

            return $request->url() . '?' . http_build_query([...$queryParameters, 'page' => $targetPage]);
        };

        return response()->json([
            'data' => $data,
            'meta' => [
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
            'links' => [
                'first' => $pageUrl(1),
                'last' => $pageUrl($paginator->lastPage()),
                'prev' => $pageUrl($paginator->currentPage() > 1 ? $paginator->currentPage() - 1 : null),
                'next' => $pageUrl($paginator->hasMorePages() ? $paginator->currentPage() + 1 : null),
            ],
        ], 200, ['Content-Type' => 'application/vnd.api+json']);
    }

    /**
     * @param array<string, mixed>|null $details
     * @return array<string, string>
     */
    private function safeDetails(?array $details): array
    {
        if ($details === null) {
            return [];
        }

        $safe = [];
        if (in_array($details['outcome'] ?? null, ['success', 'failure'], true)) {
            $safe['outcome'] = $details['outcome'];
        }

        if (
            in_array($details['stage'] ?? null, [
                'download',
                'preflight',
                'package_validation',
                'release_validation',
                'approval',
                'file_replacement',
                'extension_boot',
                'permissions',
                'migrations',
                'upgrade',
                'install',
                'update',
            ], true)
        ) {
            $safe['stage'] = $details['stage'];
        }

        return $safe;
    }
}
