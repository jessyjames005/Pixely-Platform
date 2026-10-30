<?php

declare(strict_types=1);

namespace App\Core\Updates\Services;

use App\Core\Updates\Contracts\UpdateVerifierInterface;
use App\Core\Updates\DTO\ArtifactReference;
use App\Core\Updates\Exceptions\UpdateVerificationException;

/** Verifies SHA-256 and a detached Ed25519 signature using registry-pinned public keys. */
final readonly class SodiumUpdateVerifier implements UpdateVerifierInterface
{
    /** @param array<string, string> $publicKeys Base64-encoded Ed25519 public keys indexed by key id. */
    public function __construct(private array $publicKeys)
    {
    }

    public function verify(string $artifactPath, ArtifactReference $artifact): void
    {
        if (!is_file($artifactPath) || !is_readable($artifactPath)) {
            throw new UpdateVerificationException('Artifact file does not exist or is not readable.');
        }

        $actualHash = hash_file('sha256', $artifactPath);
        if (!is_string($actualHash) || !hash_equals(strtolower($artifact->sha256), strtolower($actualHash))) {
            throw new UpdateVerificationException('Artifact SHA-256 checksum does not match registry metadata.');
        }

        if ($artifact->signature === null || $artifact->keyId === null) {
            throw new UpdateVerificationException('A trusted detached signature is required.');
        }
        if (!function_exists('sodium_crypto_sign_verify_detached')) {
            throw new UpdateVerificationException('The sodium extension is required to verify update signatures.');
        }

        $encodedKey = $this->publicKeys[$artifact->keyId] ?? null;
        $signature = base64_decode($artifact->signature, true);
        $publicKey = is_string($encodedKey) ? base64_decode($encodedKey, true) : false;
        if (!is_string($signature) || !is_string($publicKey)
            || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES
            || strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            throw new UpdateVerificationException('Unknown signing key or malformed signature metadata.');
        }

        $contents = file_get_contents($artifactPath);
        if (!is_string($contents) || !sodium_crypto_sign_verify_detached($signature, $contents, $publicKey)) {
            throw new UpdateVerificationException('Artifact signature verification failed.');
        }
    }
}
