<?php

declare(strict_types=1);

namespace App\Core\Registry\Http\Controllers;

use App\Core\Registry\Exceptions\RegistryApiException;
use App\Core\Registry\Services\RegistryPackageCatalogService;
use App\Core\Updates\Enums\UpdateTargetType;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Provides public discovery of packages available in the Registry.
 */
#[Group('Registry', weight: 11)]
final class RegistryPackageCatalogController
{
    private const MAX_QUERY_BYTES = 4096;

    private const MAX_QUERY_LENGTH = 100;

    private const MAX_CURSOR_LENGTH = 2048;

    public function __construct(
        private readonly RegistryPackageCatalogService $catalog,
    ) {
    }

    /**
     * Search or browse packages with their latest stable release summary.
     */
    #[QueryParameter('q', 'Optional case-insensitive partial match in package name or ID.')]
    #[QueryParameter('package_type', 'Optional package type filter.')]
    #[QueryParameter('per_page', 'Page size from 1 through 100.', type: 'integer', default: 20)]
    #[QueryParameter('cursor', 'Opaque cursor from the preceding response.')]
    public function index(Request $request): JsonResponse
    {
        if (strlen((string) $request->server->get('QUERY_STRING', '')) > self::MAX_QUERY_BYTES) {
            return $this->invalidQuery('The query string exceeds the supported length.');
        }

        $query = $request->query('q');
        if ($query !== null && (!is_string($query) || mb_strlen(trim($query)) > self::MAX_QUERY_LENGTH)) {
            return $this->invalidQuery('q must be a string of at most 100 characters.');
        }
        $query = is_string($query) ? trim($query) : null;
        $query = $query === '' ? null : $query;

        $packageType = $request->query('package_type');
        if (
            $packageType !== null
            && (!is_string($packageType) || UpdateTargetType::tryFrom($packageType) === null)
        ) {
            return $this->invalidQuery('package_type is not supported.');
        }

        $perPage = $request->query('per_page', '20');
        if (!is_string($perPage) || preg_match('/^[0-9]{1,3}$/', $perPage) !== 1) {
            return $this->invalidQuery('per_page must be an integer from 1 through 100.');
        }
        $perPage = (int) $perPage;
        if ($perPage < 1 || $perPage > 100) {
            return $this->invalidQuery('per_page must be an integer from 1 through 100.');
        }

        $cursor = $request->query('cursor');
        if (
            $cursor !== null
            && (!is_string($cursor) || $cursor === '' || strlen($cursor) > self::MAX_CURSOR_LENGTH)
        ) {
            return $this->invalidCursor();
        }

        try {
            $result = $this->catalog->search($query, $packageType, $perPage, $cursor);
        } catch (RegistryApiException $exception) {
            return $this->problem(
                $exception->status,
                $exception->problemCode,
                $exception->problemTitle,
                $exception->getMessage(),
            );
        } catch (QueryException) {
            return $this->problem(
                500,
                'internal_error',
                'Internal error',
                'The Registry could not complete the request.',
            );
        }

        return response()->json([
            'data' => $result['packages'],
            'meta' => [
                'per_page' => $result['per_page'],
                'next_cursor' => $result['next_cursor'],
                'has_more' => $result['has_more'],
            ],
        ], 200, ['Cache-Control' => 'public, max-age=60']);
    }

    private function invalidQuery(string $detail): JsonResponse
    {
        return $this->problem(400, 'invalid_query', 'Invalid query', $detail);
    }

    private function invalidCursor(): JsonResponse
    {
        return $this->problem(
            400,
            'invalid_cursor',
            'Invalid cursor',
            'The cursor is malformed or does not match these filters.',
        );
    }

    private function problem(int $status, string $code, string $title, string $detail): JsonResponse
    {
        return response()->json([
            'type' => 'https://registry.pixely.example/problems/' . str_replace('_', '-', $code),
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
            'code' => $code,
        ], $status, ['Content-Type' => 'application/problem+json']);
    }
}
