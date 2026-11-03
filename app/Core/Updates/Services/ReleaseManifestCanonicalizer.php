<?php

declare(strict_types=1);

namespace App\Core\Updates\Services;

use App\Core\Updates\Exceptions\UpdateVerificationException;

/**
 * Produces the RFC 8785 JSON Canonicalization Scheme representation used for
 * Pixely release-manifest signatures.
 *
 * The schema-v1 manifest contains strings, integers, booleans, null, arrays
 * and objects. Floating-point values are deliberately rejected because no
 * schema-v1 field requires them and PHP's number serialization is not a
 * substitute for ECMAScript/JCS number serialization.
 */
final class ReleaseManifestCanonicalizer
{
    /**
     * Canonicalize the manifest after removing only its root signature member.
     *
     * The input must originate from valid UTF-8 JSON parsed as stdClass objects.
     * The JSON parser/caller must reject duplicate object member names before
     * invoking this method, as required by RFC 8785.
     */
    public function canonicalizeUnsignedManifest(\stdClass $manifest): string
    {
        if (!property_exists($manifest, 'signature')) {
            throw new UpdateVerificationException('The release manifest signature member is missing.');
        }

        $unsigned = clone $manifest;
        unset($unsigned->signature);

        return $this->encodeValue($unsigned);
    }

    private function encodeValue(mixed $value): string
    {
        if ($value instanceof \stdClass) {
            return $this->encodeObject($value);
        }

        if (is_array($value)) {
            if (!array_is_list($value)) {
                throw new UpdateVerificationException('JCS arrays must be represented as JSON lists.');
            }

            return '[' . implode(',', array_map(
                fn (mixed $item): string => $this->encodeValue($item),
                $value,
            )) . ']';
        }

        if (is_float($value)) {
            throw new UpdateVerificationException(
                'Floating-point values are not supported by the Pixely schema-v1 manifest canonicalizer.',
            );
        }

        if (is_int($value) || is_bool($value) || $value === null || is_string($value)) {
            try {
                return json_encode(
                    $value,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
                );
            } catch (\JsonException $exception) {
                throw new UpdateVerificationException('A manifest value cannot be encoded as canonical JSON.', 0, $exception);
            }
        }

        throw new UpdateVerificationException('The manifest contains a value that cannot be represented in JCS.');
    }

    /**
     * Compare strings by UTF-16 code units, as required by RFC 8785.
     */
    private function compareUtf16CodeUnits(string $left, string $right): int
    {
        $leftUnits = $this->utf16CodeUnits($left);
        $rightUnits = $this->utf16CodeUnits($right);
        $length = min(count($leftUnits), count($rightUnits));

        for ($index = 0; $index < $length; $index++) {
            if ($leftUnits[$index] !== $rightUnits[$index]) {
                return $leftUnits[$index] <=> $rightUnits[$index];
            }
        }

        return count($leftUnits) <=> count($rightUnits);
    }

    /**
     * @return int[]
     */
    private function utf16CodeUnits(string $value): array
    {
        $characters = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($characters)) {
            throw new UpdateVerificationException('Manifest object keys must contain valid UTF-8.');
        }

        $units = [];
        foreach ($characters as $character) {
            $bytes = array_map('ord', str_split($character));
            $first = $bytes[0];

            if (count($bytes) === 1) {
                $codePoint = $first;
            } elseif (count($bytes) === 2) {
                $codePoint = (($first & 0x1F) << 6) | ($bytes[1] & 0x3F);
            } elseif (count($bytes) === 3) {
                $codePoint = (($first & 0x0F) << 12) | (($bytes[1] & 0x3F) << 6) | ($bytes[2] & 0x3F);
            } else {
                $codePoint = (($first & 0x07) << 18)
                    | (($bytes[1] & 0x3F) << 12)
                    | (($bytes[2] & 0x3F) << 6)
                    | ($bytes[3] & 0x3F);
            }

            if ($codePoint <= 0xFFFF) {
                $units[] = $codePoint;
                continue;
            }

            $adjusted = $codePoint - 0x10000;
            $units[] = 0xD800 + ($adjusted >> 10);
            $units[] = 0xDC00 + ($adjusted & 0x3FF);
        }

        return $units;
    }

    private function encodeObject(\stdClass $object): string
    {
        $properties = get_object_vars($object);
        $keys = array_keys($properties);

        usort($keys, function (string $left, string $right): int {
            return $this->compareUtf16CodeUnits($left, $right);
        });

        $members = [];
        foreach ($keys as $key) {
            $members[] = $this->encodeValue($key) . ':' . $this->encodeValue($properties[$key]);
        }

        return '{' . implode(',', $members) . '}';
    }
}
