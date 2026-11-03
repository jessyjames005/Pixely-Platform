<?php

declare(strict_types=1);

namespace App\Core\Updates\DTO;

/**
 * Fully typed, structurally validated representation of a Pixely release manifest.
 *
 * This DTO does not establish trust. Its signature must be verified before its
 * metadata is mapped to an UpdateCandidate or used to retrieve an artifact.
 */
final readonly class ReleaseManifest
{
    public const SCHEMA = 'pixely.release-manifest';
    public const SCHEMA_VERSION = 1;

    public function __construct(
        public ReleaseManifestPackage $package,
        public ReleaseManifestRelease $release,
        public ReleaseManifestCompatibility $compatibility,
        public ReleaseManifestDependencies $dependencies,
        public ReleaseManifestArtifact $artifact,
        public ReleaseManifestSignature $signature,
    ) {
    }

    /**
     * Build a typed manifest from decoded JSON data.
     *
     * The caller must decode JSON as an associative array and reject duplicate
     * object member names before calling this method.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        ReleaseManifestValue::assertKeys(
            $data,
            ['schema', 'schema_version', 'package', 'release', 'compatibility', 'dependencies', 'artifact', 'signature'],
            'manifest',
        );

        if (($data['schema'] ?? null) !== self::SCHEMA) {
            throw new \InvalidArgumentException('The release manifest schema identifier is unsupported.');
        }
        if (($data['schema_version'] ?? null) !== self::SCHEMA_VERSION) {
            throw new \InvalidArgumentException('The release manifest schema version is unsupported.');
        }

        foreach (['package', 'release', 'compatibility', 'dependencies', 'artifact', 'signature'] as $field) {
            if (!isset($data[$field]) || !is_array($data[$field])) {
                throw new \InvalidArgumentException(sprintf('manifest.%s must be an object.', $field));
            }
        }

        $package = ReleaseManifestPackage::fromArray($data['package']);
        $signature = ReleaseManifestSignature::fromArray($data['signature']);

        return new self(
            package: $package,
            release: ReleaseManifestRelease::fromArray($data['release']),
            compatibility: ReleaseManifestCompatibility::fromArray($data['compatibility'], $package->type),
            dependencies: ReleaseManifestDependencies::fromArray($data['dependencies']),
            artifact: ReleaseManifestArtifact::fromArray($data['artifact']),
            signature: $signature,
        );
    }
}
