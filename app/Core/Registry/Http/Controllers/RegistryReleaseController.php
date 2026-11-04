<?php

declare(strict_types=1);

namespace App\Core\Registry\Http\Controllers;

use App\Core\Registry\Exceptions\RegistryApiException;
use App\Core\Registry\Models\RegistryRelease;
use App\Core\Registry\Services\RegistryReleaseReadService;
use App\Core\Updates\DTO\ReleaseManifestValue;
use App\Core\Updates\Enums\UpdateTargetType;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Serves the public, read-only signed-manifest Registry API.
 */
#[Group('Registry', weight: 11)]
final class RegistryReleaseController
{
    private const MAX_QUERY_BYTES = 4096;

    private const MAX_PACKAGE_ID_LENGTH = 255;

    private const MAX_CURSOR_LENGTH = 2048;

    private const CHANNELS = ['stable', 'beta', 'dev'];

    public function __construct(
        private readonly RegistryReleaseReadService $releases,
    ) {
    }

    /**
     * List exact-package releases in descending SemVer order with opaque cursor pagination.
     *
     * Runtime compatibility values are accepted for contract compatibility but are
     * not used to filter results by this Registry implementation.
     */
    #[QueryParameter('package_type', 'Package type: platform, extension, theme, or plugin.', required: true)]
    #[QueryParameter('package_id', 'Exact opaque package ID.', required: true)]
    #[QueryParameter('channel', 'Release channel; defaults to stable.', default: 'stable')]
    #[QueryParameter('after_version', 'Return only SemVer releases strictly newer than this version.')]
    #[QueryParameter('per_page', 'Page size from 1 through 100.', type: 'integer', default: 20)]
    #[QueryParameter('cursor', 'Opaque cursor from the preceding response.')]
    #[QueryParameter('runtime[php]', 'Advisory PHP SemVer runtime version; currently ignored.')]
    #[QueryParameter('runtime[pixely]', 'Advisory Pixely SemVer runtime version; currently ignored.')]
    #[QueryParameter('runtime[laravel]', 'Advisory Laravel SemVer runtime version; currently ignored.')]
    public function index(Request $request): Response|JsonResponse
    {
        if (strlen((string) $request->server->get('QUERY_STRING', '')) > self::MAX_QUERY_BYTES) {
            return $this->invalidQuery('The query string exceeds the supported length.');
        }

        $type = $request->query('package_type');
        $packageId = $request->query('package_id');
        $channel = $request->query('channel', 'stable');
        $afterVersion = $request->query('after_version');
        $perPage = $request->query('per_page', '20');
        $cursor = $request->query('cursor');

        if (!$this->isPackageType($type) || !$this->isPackageId($packageId)) {
            return $this->invalidQuery('A supported package_type and exact non-empty package_id are required.');
        }

        if (!is_string($channel) || !in_array($channel, self::CHANNELS, true)) {
            return $this->invalidQuery('The requested channel is not supported.');
        }

        if ($afterVersion !== null && (!$this->isSemver($afterVersion))) {
            return $this->invalidQuery('after_version must be a valid SemVer version.');
        }

        $runtimeFilters = $this->normalizeRuntimeFilters($request->query('runtime'));
        if ($runtimeFilters === null) {
            return $this->invalidQuery(
                'runtime must contain only scalar SemVer values for php, pixely, or laravel.',
            );
        }

        if (!is_string($perPage) || preg_match('/^[0-9]{1,3}$/', $perPage) !== 1) {
            return $this->invalidQuery('per_page must be an integer from 1 through 100.');
        }
        $perPage = (int) $perPage;

        if ($perPage < 1 || $perPage > 100) {
            return $this->invalidQuery('per_page must be an integer from 1 through 100.');
        }

        if (
            $cursor !== null
            && (!is_string($cursor) || $cursor === '' || strlen($cursor) > self::MAX_CURSOR_LENGTH)
        ) {
            return $this->problem(
                400,
                'invalid_cursor',
                'Invalid cursor',
                'The cursor is malformed or does not match these filters.',
            );
        }

        try {
            $page = $this->releases->list(
                $type,
                $packageId,
                $channel,
                $afterVersion,
                $perPage,
                $runtimeFilters,
                $cursor,
            );
        } catch (RegistryApiException $exception) {
            return $this->registryProblem($exception);
        } catch (QueryException) {
            return $this->problem(
                500,
                'internal_error',
                'Internal error',
                'The Registry could not complete the request.',
            );
        }

        $items = array_map(
            static fn (RegistryRelease $release): string => '{"manifest":' . $release->manifest_json . '}',
            $page['releases'],
        );
        $meta = json_encode([
            'per_page' => $page['per_page'],
            'next_cursor' => $page['next_cursor'],
            'has_more' => $page['has_more'],
        ], JSON_THROW_ON_ERROR);

        return response(
            '{"data":[' . implode(',', $items) . '],"meta":' . $meta . '}',
            SymfonyResponse::HTTP_OK,
            [
                'Content-Type' => 'application/json; charset=utf-8',
                'Cache-Control' => 'public, max-age=60',
            ],
        );
    }

    /**
     * Resolve one immutable release by exact package identity and SemVer version.
     */
    #[QueryParameter('package_type', 'Package type: platform, extension, theme, or plugin.', required: true)]
    #[QueryParameter('package_id', 'Exact opaque package ID.', required: true)]
    #[QueryParameter('version', 'Exact SemVer release version.', required: true)]
    public function resolve(Request $request): Response|JsonResponse
    {
        if (strlen((string) $request->server->get('QUERY_STRING', '')) > self::MAX_QUERY_BYTES) {
            return $this->invalidQuery('The query string exceeds the supported length.');
        }

        $type = $request->query('package_type');
        $packageId = $request->query('package_id');
        $version = $request->query('version');

        if (
            !$this->isPackageType($type)
            || !$this->isPackageId($packageId)
            || !$this->isSemver($version)
        ) {
            return $this->invalidQuery(
                'A supported package_type, exact non-empty package_id, and valid version are required.',
            );
        }

        try {
            $release = $this->releases->resolve($type, $packageId, $version);
        } catch (RegistryApiException $exception) {
            return $this->registryProblem($exception);
        } catch (QueryException) {
            return $this->problem(
                500,
                'internal_error',
                'Internal error',
                'The Registry could not complete the request.',
            );
        }

        return response(
            '{"data":{"manifest":' . $release->manifest_json . '}}',
            SymfonyResponse::HTTP_OK,
            [
                'Content-Type' => 'application/json; charset=utf-8',
                'Cache-Control' => 'public, max-age=300',
                'ETag' => '"' . hash('sha256', $release->manifest_json) . '"',
            ],
        );
    }

    private function isPackageType(mixed $value): bool
    {
        return is_string($value) && UpdateTargetType::tryFrom($value) !== null;
    }

    private function isPackageId(mixed $value): bool
    {
        return is_string($value)
            && $value !== ''
            && mb_strlen($value) <= self::MAX_PACKAGE_ID_LENGTH;
    }

    private function isSemver(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        try {
            ReleaseManifestValue::assertSemver($value, 'version');
        } catch (\InvalidArgumentException) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, string>|null Null indicates malformed runtime input.
     */
    private function normalizeRuntimeFilters(mixed $runtime): ?array
    {
        if ($runtime === null) {
            return [];
        }

        if (!is_array($runtime) || array_is_list($runtime)) {
            return null;
        }

        $normalized = [];
        foreach (['php', 'pixely', 'laravel'] as $name) {
            if (!array_key_exists($name, $runtime)) {
                continue;
            }

            $version = $runtime[$name];
            if (
                !is_string($version)
                || !$this->isSemver($version)
            ) {
                return null;
            }

            $normalized[$name] = $version;
        }

        if (count($normalized) !== count($runtime)) {
            return null;
        }

        return $normalized;
    }

    private function invalidQuery(string $detail): JsonResponse
    {
        return $this->problem(400, 'invalid_query', 'Invalid query', $detail);
    }

    private function registryProblem(RegistryApiException $exception): JsonResponse
    {
        return $this->problem(
            $exception->status,
            $exception->problemCode,
            $exception->problemTitle,
            $exception->getMessage(),
        );
    }

    private function problem(int $status, string $code, string $title, string $detail): JsonResponse
    {
        $slug = str_replace('_', '-', $code);

        return response()->json([
            'type' => 'https://registry.pixely.example/problems/' . $slug,
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
            'code' => $code,
        ], $status, ['Content-Type' => 'application/problem+json']);
    }
}
