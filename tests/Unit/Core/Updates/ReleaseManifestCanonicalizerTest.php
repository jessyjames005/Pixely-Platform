<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Updates;

use App\Core\Updates\DTO\ReleaseManifestSignature;
use App\Core\Updates\Exceptions\UpdateVerificationException;
use App\Core\Updates\Services\ReleaseManifestCanonicalizer;
use App\Core\Updates\Services\SodiumReleaseManifestVerifier;
use PHPUnit\Framework\TestCase;

final class ReleaseManifestCanonicalizerTest extends TestCase
{
    public function test_canonicalizes_objects_recursively_and_removes_only_root_signature(): void
    {
        $manifest = json_decode(
            '{"z":1,"nested":{"b":2,"a":1},"signature":{"key_id":"key-1"},"a":"value"}',
            false,
            512,
            JSON_THROW_ON_ERROR,
        );

        $canonical = (new ReleaseManifestCanonicalizer())->canonicalizeUnsignedManifest($manifest);

        self::assertSame('{"a":"value","nested":{"a":1,"b":2},"z":1}', $canonical);
    }

    public function test_uses_utf16_code_unit_order_for_object_keys(): void
    {
        $manifest = json_decode('{"\uE000":1,"\uD800\uDC00":2,"signature":{}}', false, 512, JSON_THROW_ON_ERROR);

        $canonical = (new ReleaseManifestCanonicalizer())->canonicalizeUnsignedManifest($manifest);

        self::assertSame('{"𐀀":2,"":1}', $canonical);
    }

    public function test_rejects_floating_point_values_outside_schema_v1_contract(): void
    {
        $manifest = json_decode('{"size":1.5,"signature":{}}', false, 512, JSON_THROW_ON_ERROR);

        $this->expectException(UpdateVerificationException::class);
        (new ReleaseManifestCanonicalizer())->canonicalizeUnsignedManifest($manifest);
    }

    public function test_requires_root_signature_member(): void
    {
        $manifest = json_decode('{"schema":"pixely.release-manifest"}', false, 512, JSON_THROW_ON_ERROR);

        $this->expectException(UpdateVerificationException::class);
        (new ReleaseManifestCanonicalizer())->canonicalizeUnsignedManifest($manifest);
    }
}
