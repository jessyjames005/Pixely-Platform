<?php

declare(strict_types=1);

namespace App\Core\Updates\Services;

use App\Core\Updates\Contracts\UpdateRegistryInterface;
use App\Core\Updates\DTO\ArtifactReference;
use App\Core\Updates\DTO\ReleaseManifest;
use App\Core\Updates\DTO\ReleaseManifestSignature;
use App\Core\Updates\DTO\UpdateCandidate;
use App\Core\Updates\DTO\UpdateQuery;
use App\Core\Updates\Enums\UpdateTargetType;
use App\Core\Updates\Exceptions\RegistryApiException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Reads and authenticates release manifests from the official Pixely Registry.
 *
 * A response is never mapped to an UpdateCandidate until its Ed25519
 * manifest signature has been verified against locally configured keys.
 */
final readonly class HttpUpdateRegistry implements UpdateRegistryInterface
{
    public function __construct(
        private string $baseUrl,
        private int $timeoutSeconds,
        private int $perPage,
        private JsonObjectKeyUniquenessGuard $jsonGuard,
        private SodiumReleaseManifestVerifier $manifestVerifier,
    ) {
        $scheme = parse_url($this->baseUrl, PHP_URL_SCHEME);
        $host = parse_url($this->baseUrl, PHP_URL_HOST);

        if (
            filter_var($this->baseUrl, FILTER_VALIDATE_URL) === false
            || strtolower((string) $scheme) !== 'https'
            || !is_string($host)
            || $host === ''
        ) {
            throw new \InvalidArgumentException('The Pixely Registry base URL must be an absolute HTTPS URL.');
        }

        if ($this->timeoutSeconds < 1 || $this->perPage < 1 || $this->perPage > 100) {
            throw new \InvalidArgumentException('The Registry timeout or page size is outside the supported range.');
        }
    }

    /** @return iterable<UpdateCandidate> */
    public function findUpdates(UpdateQuery $query): iterable
    {
        $cursor = null;
        $seenCursors = [];
        $candidates = [];

        do {
            $parameters = [
                'package_type' => $query->type->value,
                'package_id' => $query->packageId,
                'channel' => $query->channel->value,
                'after_version' => $query->installedVersion,
                'per_page' => $this->perPage,
            ];

            foreach ($query->runtimeVersions as $runtime => $version) {
                if (is_string($runtime) && is_string($version) && $version !== '') {
                    $parameters['runtime'][$runtime] = $version;
                }
            }

            if ($cursor !== null) {
                $parameters['cursor'] = $cursor;
            }

            $payload = $this->get('/releases', $parameters);
            if ($payload === null) {
                throw new RegistryApiException('The Registry list request unexpectedly returned no payload.');
            }

            $data = $payload->data ?? null;
            $meta = $payload->meta ?? null;

            if (!is_array($data) || !($meta instanceof \stdClass)) {
                throw new RegistryApiException('The Registry list response has an invalid envelope.');
            }

            foreach ($data as $item) {
                $candidate = $this->candidateFromEnvelope($item);
                if (
                    $candidate->type === $query->type
                    && $candidate->packageId === $query->packageId
                    && $candidate->channel === $query->channel
                    && version_compare($candidate->version, $query->installedVersion, '>')
                ) {
                    $candidates[] = $candidate;
                }
            }

            $pageSize = $meta->per_page ?? null;
            $hasMore = $meta->has_more ?? null;
            $nextCursor = $meta->next_cursor ?? null;

            if (
                !is_int($pageSize)
                || $pageSize < 1
                || $pageSize > 100
                || !is_bool($hasMore)
                || ($hasMore && (!is_string($nextCursor) || $nextCursor === ''))
                || (!$hasMore && $nextCursor !== null)
            ) {
                throw new RegistryApiException('The Registry pagination metadata is invalid.');
            }

            if (!$hasMore) {
                break;
            }

            if (isset($seenCursors[$nextCursor])) {
                throw new RegistryApiException('The Registry returned a repeated pagination cursor.');
            }

            $seenCursors[$nextCursor] = true;
            $cursor = $nextCursor;
        } while (count($seenCursors) < 1000);

        if (count($seenCursors) >= 1000) {
            throw new RegistryApiException('The Registry pagination limit was exceeded.');
        }

        usort(
            $candidates,
            static fn (UpdateCandidate $left, UpdateCandidate $right): int => version_compare(
                $right->version,
                $left->version,
            ),
        );

        return $candidates;
    }

    public function findRelease(
        UpdateTargetType $type,
        string $packageId,
        string $version,
    ): ?UpdateCandidate {
        $payload = $this->get('/releases/resolve', [
            'package_type' => $type->value,
            'package_id' => $packageId,
            'version' => $version,
        ], notFoundAsNull: true);

        if ($payload === null) {
            return null;
        }

        $item = $payload->data ?? null;
        if (!$item instanceof \stdClass) {
            throw new RegistryApiException('The Registry exact-release response has an invalid envelope.');
        }

        $candidate = $this->candidateFromEnvelope($item);

        if (
            $candidate->type !== $type
            || $candidate->packageId !== $packageId
            || $candidate->version !== $version
        ) {
            throw new RegistryApiException('The Registry returned a release with an unexpected identity.');
        }

        return $candidate;
    }

    /** @param array<string, mixed> $parameters */
    private function get(string $path, array $parameters, bool $notFoundAsNull = false): ?\stdClass
    {
        $url = rtrim($this->baseUrl, '/') . '/api/v1' . $path;

        try {
            $response = $this->request()->get($url, $parameters);
        } catch (Throwable $exception) {
            throw new RegistryApiException('The Pixely Registry request failed.', 0, $exception);
        }

        if ($notFoundAsNull && $response->status() === 404) {
            return null;
        }

        if (!$response->successful()) {
            throw new RegistryApiException(sprintf(
                'The Pixely Registry returned HTTP %d.',
                $response->status(),
            ));
        }

        $body = $response->body();
        try {
            $this->jsonGuard->assertUniqueObjectKeys($body);
            $payload = json_decode($body, false, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new RegistryApiException('The Pixely Registry returned invalid or ambiguous JSON.', 0, $exception);
        }

        if (!$payload instanceof \stdClass) {
            throw new RegistryApiException('The Pixely Registry response must be a JSON object.');
        }

        return $payload;
    }

    private function request(): PendingRequest
    {
        return Http::acceptJson()
            ->withOptions(['allow_redirects' => false])
            ->timeout($this->timeoutSeconds);
    }

    private function candidateFromEnvelope(mixed $item): UpdateCandidate
    {
        if (!($item instanceof \stdClass) || !(($item->manifest ?? null) instanceof \stdClass)) {
            throw new RegistryApiException('A Registry release item must contain a manifest object.');
        }

        $rawManifest = $item->manifest;
        $signatureData = $rawManifest->signature ?? null;

        if (!$signatureData instanceof \stdClass) {
            throw new RegistryApiException('The Registry manifest signature envelope is missing.');
        }

        try {
            $signature = new ReleaseManifestSignature(
                algorithm: $signatureData->algorithm ?? '',
                keyId: $signatureData->key_id ?? '',
                value: $signatureData->value ?? '',
            );
            $this->manifestVerifier->verify($rawManifest, $signature);

            // Decode again as associative data only after authenticity succeeds.
            $manifestArray = json_decode(
                json_encode($rawManifest, JSON_THROW_ON_ERROR),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
            $manifest = ReleaseManifest::fromArray($manifestArray);
        } catch (Throwable $exception) {
            throw new RegistryApiException('The Registry returned an invalid or untrusted release manifest.', 0, $exception);
        }

        return new UpdateCandidate(
            type: $manifest->package->type,
            packageId: $manifest->package->id,
            name: $manifest->package->name,
            version: $manifest->release->version,
            channel: $manifest->release->channel,
            artifact: new ArtifactReference(
                provider: $manifest->artifact->provider,
                uri: $manifest->artifact->uri,
                sha256: $manifest->artifact->sha256,
                signature: $manifest->artifact->signature->value,
                keyId: $manifest->artifact->signature->keyId,
            ),
            requires: $manifest->dependencies->requires,
            conflicts: $manifest->dependencies->conflicts,
            runtimeConstraints: $manifest->compatibility->constraints,
            releaseNotes: $manifest->release->releaseNotes,
            publishedAt: $manifest->release->publishedAt,
        );
    }
}
