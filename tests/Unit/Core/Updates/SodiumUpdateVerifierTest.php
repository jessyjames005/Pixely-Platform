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
        $this->directory = sys_get_temp_dir().'/pixely-update-test-'.bin2hex(random_bytes(8));
        mkdir($this->directory, 0700, true);
        $this->path = $this->directory.'/artifact.zip';
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
        if (!function_exists('sodium_crypto_sign_keypair')) {
            self::markTestSkipped('The sodium extension is not installed.');
        }
        $contents = 'safe test archive bytes';
        file_put_contents($this->path, $contents);
        $pair = sodium_crypto_sign_keypair();
        $secret = sodium_crypto_sign_secretkey($pair);
        $public = sodium_crypto_sign_publickey($pair);
        $signature = sodium_crypto_sign_detached($contents, $secret);
        $reference = new ArtifactReference(ArtifactProvider::Pixely, 'https://updates.pixely.example/package.zip', hash('sha256', $contents), base64_encode($signature), 'test-key');

        (new SodiumUpdateVerifier(['test-key' => base64_encode($public)]))->verify($this->path, $reference);
        self::assertTrue(true);
    }

    public function test_rejects_checksum_mismatch(): void
    {
        file_put_contents($this->path, 'tampered');
        $reference = new ArtifactReference(ArtifactProvider::Pixely, 'https://updates.pixely.example/package.zip', str_repeat('a', 64), base64_encode(str_repeat('x', 64)), 'test-key');

        $this->expectException(UpdateVerificationException::class);
        (new SodiumUpdateVerifier([]))->verify($this->path, $reference);
    }

    public function test_rejects_missing_signature_even_when_checksum_matches(): void
    {
        file_put_contents($this->path, 'archive');
        $reference = new ArtifactReference(ArtifactProvider::Pixely, 'https://updates.pixely.example/package.zip', hash_file('sha256', $this->path));

        $this->expectException(UpdateVerificationException::class);
        (new SodiumUpdateVerifier([]))->verify($this->path, $reference);
    }
}
