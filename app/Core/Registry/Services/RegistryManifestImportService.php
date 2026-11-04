<?php

declare(strict_types=1);

namespace App\Core\Registry\Services;

use App\Core\Registry\Exceptions\RegistryImmutableReleaseException;
use App\Core\Registry\Models\RegistryPackage;
use App\Core\Registry\Models\RegistryRelease;
use App\Core\Updates\DTO\ReleaseManifest;
use App\Core\Updates\DTO\ReleaseManifestSignature;
use App\Core\Updates\Services\JsonObjectKeyUniquenessGuard;
use App\Core\Updates\Services\ReleaseManifestCanonicalizer;
use App\Core\Updates\Services\SodiumReleaseManifestVerifier;
use Composer\Semver\Comparator;
use Illuminate\Support\Facades\DB;
use stdClass;

final readonly class RegistryManifestImportService
{
    public function __construct(
        private JsonObjectKeyUniquenessGuard $jsonGuard,
        private ReleaseManifestCanonicalizer $canonicalizer,
        private SodiumReleaseManifestVerifier $manifestVerifier,
    ) {
    }

    /**
     * Import a complete signed manifest; return false when the identical release already exists.
     */
    public function import(string $rawManifestJson): bool
    {
        $this->jsonGuard->assertUniqueObjectKeys($rawManifestJson);
        $rawManifest = json_decode($rawManifestJson, false, 512, JSON_THROW_ON_ERROR);

        if (!$rawManifest instanceof stdClass) {
            throw new \InvalidArgumentException('The release manifest must be a JSON object.');
        }

        $signatureData = $rawManifest->signature ?? null;
        if (!$signatureData instanceof stdClass) {
            throw new \InvalidArgumentException('The release manifest signature envelope is missing.');
        }

        $signature = ReleaseManifestSignature::fromArray(get_object_vars($signatureData));
        $this->manifestVerifier->verify($rawManifest, $signature);

        $manifestData = json_decode($rawManifestJson, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($manifestData)) {
            throw new \InvalidArgumentException('The release manifest must be a JSON object.');
        }

        $manifest = ReleaseManifest::fromArray($manifestData);
        $fingerprint = $this->fingerprint($rawManifest, $signature);

        return DB::transaction(function () use ($manifest, $rawManifestJson, $fingerprint): bool {
            $package = RegistryPackage::query()
                ->where('type', $manifest->package->type->value)
                ->where('package_id', $manifest->package->id)
                ->lockForUpdate()
                ->first();

            if ($package === null) {
                $package = RegistryPackage::query()->create([
                    'type' => $manifest->package->type->value,
                    'package_id' => $manifest->package->id,
                    'name' => $manifest->package->name,
                ]);
            }

            $existingRelease = RegistryRelease::query()
                ->where('registry_package_id', $package->id)
                ->where('version', $manifest->release->version)
                ->first();

            if ($existingRelease !== null) {
                if (!hash_equals($existingRelease->manifest_fingerprint, $fingerprint)) {
                    throw new RegistryImmutableReleaseException(sprintf(
                        'Release %s/%s/%s is already published with different signed content.',
                        $manifest->package->type->value,
                        $manifest->package->id,
                        $manifest->release->version,
                    ));
                }

                return false;
            }

            $package->releases()->create([
                'version' => $manifest->release->version,
                'channel' => $manifest->release->channel->value,
                'published_at' => $manifest->release->publishedAt,
                'package_name' => $manifest->package->name,
                'manifest_json' => $rawManifestJson,
                'manifest_fingerprint' => $fingerprint,
            ]);

            $this->refreshPackageName($package);

            return true;
        });
    }

    private function fingerprint(stdClass $rawManifest, ReleaseManifestSignature $signature): string
    {
        $canonicalUnsignedManifest = $this->canonicalizer->canonicalizeUnsignedManifest($rawManifest);
        $signatureEnvelope = json_encode([
            'algorithm' => $signature->algorithm,
            'key_id' => $signature->keyId,
            'value' => $signature->value,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return hash('sha256', $canonicalUnsignedManifest . "\0" . $signatureEnvelope);
    }

    private function refreshPackageName(RegistryPackage $package): void
    {
        $latestRelease = null;
        foreach ($package->releases()->get() as $release) {
            if ($latestRelease === null || $this->compareReleaseOrder($release, $latestRelease) > 0) {
                $latestRelease = $release;
            }
        }

        if ($latestRelease !== null && $package->name !== $latestRelease->package_name) {
            $package->name = $latestRelease->package_name;
            $package->save();
        }
    }

    private function compareReleaseOrder(RegistryRelease $left, RegistryRelease $right): int
    {
        $timestampComparison = $left->published_at->getTimestamp() <=> $right->published_at->getTimestamp();
        if ($timestampComparison !== 0) {
            return $timestampComparison;
        }

        $microsecondComparison = $left->published_at->format('u') <=> $right->published_at->format('u');
        if ($microsecondComparison !== 0) {
            return $microsecondComparison;
        }

        if (Comparator::greaterThan($left->version, $right->version)) {
            return 1;
        }
        if (Comparator::lessThan($left->version, $right->version)) {
            return -1;
        }

        return strcmp($left->version, $right->version);
    }
}
