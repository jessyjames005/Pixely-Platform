<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Updates;

use App\Core\Updates\DTO\ArtifactReference;
use App\Core\Updates\Enums\ArtifactProvider;
use App\Core\Updates\Exceptions\UpdateVerificationException;
use App\Core\Updates\Services\SodiumUpdateVerifier;
use PHPUnit\Framework\TestCase;

final class SodiumUpdateVerifierTest extends TestCase
{
    private string $directory;
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = getcwd() . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR
            . 'framework' . DIRECTORY_SEPARATOR . 'testing' . DIRECTORY_SEPARATOR
            . 'pixely-update-test-' . bin2hex(random_bytes(8));
        mkdir($this->directory, 0700, true);
        $this->path = $this->directory . '/artifact.zip';
    }

    protected function tearDown(): void
    {
        if (isset($this->path) && is_file($this->path)) {
            unlink($this->path);
        }
        if (isset($this->directory) && is_dir($this->directory)) {
            rmdir($this->directory);
        }
        parent::tearDown();
    }

    public function test_accepts_matching_checksum_and_ed25519_signature(): void
    {
        $contents = 'safe test archive bytes';
        file_put_contents($this->path, $contents);
        [$reference, $publicKey] = $this->signedArtifact($contents);

        (new SodiumUpdateVerifier(['test-key' => $publicKey]))->verify($this->path, $reference);
        self::assertTrue(true);
    }

    public function test_rejects_an_artifact_size_mismatch(): void
    {
        $contents = 'archive';
        file_put_contents($this->path, $contents);
        $reference = new ArtifactReference(
            ArtifactProvider::Pixely,
            'https://updates.pixely.example/package.zip',
            hash('sha256', $contents),
            'signature',
            'test-key',
            expectedSize: strlen($contents) + 1,
        );

        $this->expectException(UpdateVerificationException::class);
        (new SodiumUpdateVerifier([]))->verify($this->path, $reference);
    }

    public function test_rejects_checksum_mismatch(): void
    {
        file_put_contents($this->path, 'tampered');
        $reference = new ArtifactReference(
            ArtifactProvider::Pixely,
            'https://updates.pixely.example/package.zip',
            str_repeat('a', 64),
            base64_encode(str_repeat('x', 64)),
            'test-key',
            expectedSize: strlen('tampered'),
        );

        $this->expectException(UpdateVerificationException::class);
        (new SodiumUpdateVerifier([]))->verify($this->path, $reference);
    }

    public function test_rejects_missing_expected_size(): void
    {
        $contents = 'archive';
        file_put_contents($this->path, $contents);
        $reference = new ArtifactReference(
            ArtifactProvider::Pixely,
            'https://updates.pixely.example/package.zip',
            hash('sha256', $contents),
        );

        $this->expectException(UpdateVerificationException::class);
        (new SodiumUpdateVerifier([]))->verify($this->path, $reference);
    }

    public function test_rejects_missing_signature_even_when_checksum_matches(): void
    {
        $contents = 'archive';
        file_put_contents($this->path, $contents);
        $reference = new ArtifactReference(
            ArtifactProvider::Pixely,
            'https://updates.pixely.example/package.zip',
            hash('sha256', $contents),
            expectedSize: strlen($contents),
        );

        $this->expectException(UpdateVerificationException::class);
        (new SodiumUpdateVerifier([]))->verify($this->path, $reference);
    }

    public function test_rejects_malformed_signature_metadata(): void
    {
        $contents = 'archive';
        file_put_contents($this->path, $contents);
        $reference = new ArtifactReference(
            ArtifactProvider::Pixely,
            'https://updates.pixely.example/package.zip',
            hash('sha256', $contents),
            'not-base64',
            'test-key',
            expectedSize: strlen($contents),
        );

        $this->expectException(UpdateVerificationException::class);
        (new SodiumUpdateVerifier(['test-key' => base64_encode(str_repeat("\0", SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES))]))
            ->verify($this->path, $reference);
    }

    public function test_rejects_a_signature_from_an_untrusted_key(): void
    {
        if (!function_exists('sodium_crypto_sign_keypair')) {
            self::markTestSkipped('The sodium extension is not installed.');
        }

        $contents = 'archive';
        file_put_contents($this->path, $contents);
        [$reference] = $this->signedArtifact($contents);

        $this->expectException(UpdateVerificationException::class);
        (new SodiumUpdateVerifier([]))->verify($this->path, $reference);
    }

    /** @return array{ArtifactReference, string} */
    private function signedArtifact(string $contents): array
    {
        if (!function_exists('sodium_crypto_sign_keypair')) {
            self::markTestSkipped('The sodium extension is not installed.');
        }

        $pair = sodium_crypto_sign_keypair();
        $secret = sodium_crypto_sign_secretkey($pair);
        $public = sodium_crypto_sign_publickey($pair);
        $signature = sodium_crypto_sign_detached($contents, $secret);

        return [
            new ArtifactReference(
                ArtifactProvider::Pixely,
                'https://updates.pixely.example/package.zip',
                hash('sha256', $contents),
                base64_encode($signature),
                'test-key',
                expectedSize: strlen($contents),
            ),
            base64_encode($public),
        ];
    }
}
