<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Updates;

use App\Core\Updates\DTO\ReleaseManifestSignature;
use App\Core\Updates\Exceptions\UpdateVerificationException;
use App\Core\Updates\Services\ReleaseManifestCanonicalizer;
use App\Core\Updates\Services\SodiumReleaseManifestVerifier;
use PHPUnit\Framework\TestCase;

final class SodiumReleaseManifestVerifierTest extends TestCase
{
    public function test_accepts_a_manifest_signed_by_a_locally_trusted_key(): void
    {
        if (!function_exists('sodium_crypto_sign_keypair')) {
            self::markTestSkipped('The sodium extension is not installed.');
        }

        $manifest = json_decode(
            '{"schema":"pixely.release-manifest","schema_version":1,"package":{"id":"pixely/gallery"},"signature":{}}',
            false,
            512,
            JSON_THROW_ON_ERROR,
        );
        $canonical = (new ReleaseManifestCanonicalizer())->canonicalizeUnsignedManifest($manifest);
        $keyPair = sodium_crypto_sign_keypair();
        $secretKey = sodium_crypto_sign_secretkey($keyPair);
        $publicKey = sodium_crypto_sign_publickey($keyPair);
        $signatureBytes = sodium_crypto_sign_detached($canonical, $secretKey);
        $signature = new ReleaseManifestSignature('Ed25519', 'trusted-manifest-key', base64_encode($signatureBytes));
        $manifest->signature = (object) [
            'algorithm' => $signature->algorithm,
            'key_id' => $signature->keyId,
            'value' => $signature->value,
        ];

        (new SodiumReleaseManifestVerifier(
            new ReleaseManifestCanonicalizer(),
            ['trusted-manifest-key' => base64_encode($publicKey)],
        ))->verify($manifest, $signature);

        self::assertTrue(true);
    }

    public function test_rejects_a_manifest_modified_after_signing(): void
    {
        if (!function_exists('sodium_crypto_sign_keypair')) {
            self::markTestSkipped('The sodium extension is not installed.');
        }

        $manifest = json_decode('{"schema":"pixely.release-manifest","signature":{}}', false, 512, JSON_THROW_ON_ERROR);
        $canonicalizer = new ReleaseManifestCanonicalizer();
        $keyPair = sodium_crypto_sign_keypair();
        $secretKey = sodium_crypto_sign_secretkey($keyPair);
        $publicKey = sodium_crypto_sign_publickey($keyPair);
        $signatureBytes = sodium_crypto_sign_detached(
            $canonicalizer->canonicalizeUnsignedManifest($manifest),
            $secretKey,
        );
        $manifest->schema = 'pixely.tampered-manifest';
        $signature = new ReleaseManifestSignature('Ed25519', 'trusted-key', base64_encode($signatureBytes));
        $manifest->signature = (object) [
            'algorithm' => $signature->algorithm,
            'key_id' => $signature->keyId,
            'value' => $signature->value,
        ];

        $this->expectException(UpdateVerificationException::class);
        (new SodiumReleaseManifestVerifier($canonicalizer, ['trusted-key' => base64_encode($publicKey)]))
            ->verify($manifest, $signature);
    }

    public function test_rejects_an_unknown_manifest_signing_key(): void
    {
        if (!function_exists('sodium_crypto_sign_keypair')) {
            self::markTestSkipped('The sodium extension is not installed.');
        }

        $manifest = json_decode('{"schema":"pixely.release-manifest","signature":{}}', false, 512, JSON_THROW_ON_ERROR);
        $keyPair = sodium_crypto_sign_keypair();
        $signatureBytes = sodium_crypto_sign_detached(
            (new ReleaseManifestCanonicalizer())->canonicalizeUnsignedManifest($manifest),
            sodium_crypto_sign_secretkey($keyPair),
        );
        $signature = new ReleaseManifestSignature('Ed25519', 'unknown-key', base64_encode($signatureBytes));
        $manifest->signature = (object) [
            'algorithm' => $signature->algorithm,
            'key_id' => $signature->keyId,
            'value' => $signature->value,
        ];

        $this->expectException(UpdateVerificationException::class);
        (new SodiumReleaseManifestVerifier(new ReleaseManifestCanonicalizer(), []))->verify($manifest, $signature);
    }
}
