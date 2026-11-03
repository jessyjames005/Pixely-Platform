<?php

declare(strict_types=1);

namespace App\Core\Updates\Services;

use App\Core\Updates\DTO\ReleaseManifestSignature;
use App\Core\Updates\Exceptions\UpdateVerificationException;

/**
 * Verifies release-manifest signatures against locally configured trust keys.
 *
 * Public keys are selected only from local configuration. A key supplied by
 * the manifest is never accepted as a trust anchor.
 */
final readonly class SodiumReleaseManifestVerifier
{
    /** @param array<string, string> $publicKeys Base64-encoded Ed25519 public keys indexed by key ID. */
    public function __construct(
        private ReleaseManifestCanonicalizer $canonicalizer,
        private array $publicKeys,
    ) {
    }

    /**
     * Verify the root signature of a decoded JSON manifest.
     *
     * The manifest must be parsed from valid UTF-8 JSON into stdClass objects,
     * and duplicate object member names must have been rejected by the parser.
     */
    public function verify(\stdClass $manifest, ReleaseManifestSignature $signature): void
    {
        if (!function_exists('sodium_crypto_sign_verify_detached')) {
            throw new UpdateVerificationException('The sodium extension is required to verify release manifests.');
        }

        $manifestSignature = $manifest->signature ?? null;
        if (
            !$manifestSignature instanceof \stdClass
            || ($manifestSignature->algorithm ?? null) !== $signature->algorithm
            || ($manifestSignature->key_id ?? null) !== $signature->keyId
            || ($manifestSignature->value ?? null) !== $signature->value
        ) {
            throw new UpdateVerificationException('The supplied signature envelope does not match the manifest.');
        }

        $encodedKey = $this->publicKeys[$signature->keyId] ?? null;
        $publicKey = is_string($encodedKey) ? base64_decode($encodedKey, true) : false;
        $signatureBytes = base64_decode($signature->value, true);

        if (
            !is_string($publicKey)
            || base64_encode($publicKey) !== $encodedKey
            || strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
            || !is_string($signatureBytes)
            || strlen($signatureBytes) !== SODIUM_CRYPTO_SIGN_BYTES
        ) {
            throw new UpdateVerificationException('Unknown manifest signing key or malformed signature metadata.');
        }

        $canonical = $this->canonicalizer->canonicalizeUnsignedManifest($manifest);

        if (!sodium_crypto_sign_verify_detached($signatureBytes, $canonical, $publicKey)) {
            throw new UpdateVerificationException('Release manifest signature verification failed.');
        }
    }
}
