<?php

declare(strict_types=1);

namespace App\Core\Updates\Services;

/**
 * Rejects duplicate member names before PHP decodes JSON objects.
 *
 * PHP's json_decode silently keeps the last occurrence of a duplicate object
 * member. Signed manifests must not have ambiguous JSON representations.
 */
final class JsonObjectKeyUniquenessGuard
{
    private int $offset = 0;
    private int $length = 0;

    public function assertUniqueObjectKeys(string $json): void
    {
        $this->offset = 0;
        $this->length = strlen($json);
        $this->parseValue($json);
        $this->skipWhitespace($json);

        if ($this->offset !== $this->length) {
            throw new \InvalidArgumentException('The Registry response contains trailing JSON data.');
        }
    }

    private function parseValue(string $json): void
    {
        $this->skipWhitespace($json);
        $character = $json[$this->offset] ?? '';

        if ($character === '{') {
            $this->parseObject($json);
            return;
        }

        if ($character === '[') {
            $this->parseArray($json);
            return;
        }

        if ($character === '"') {
            $this->parseString($json);
            return;
        }

        $remaining = substr($json, $this->offset);
        if (preg_match('/\A(?:true|false|null|-?(?:0|[1-9]\d*)(?:\.\d+)?(?:[eE][+-]?\d+)?)/', $remaining, $match) !== 1) {
            throw new \InvalidArgumentException('The Registry response is not valid JSON.');
        }

        $this->offset += strlen($match[0]);
    }

    private function parseObject(string $json): void
    {
        $this->offset++;
        $this->skipWhitespace($json);

        if (($json[$this->offset] ?? '') === '}') {
            $this->offset++;
            return;
        }

        $keys = [];
        while (true) {
            $this->skipWhitespace($json);
            if (($json[$this->offset] ?? '') !== '"') {
                throw new \InvalidArgumentException('A JSON object member name must be a string.');
            }

            $rawKey = $this->parseString($json);
            $key = json_decode($rawKey, true, 512, JSON_THROW_ON_ERROR);
            if (isset($keys[$key])) {
                throw new \InvalidArgumentException('The Registry response contains a duplicate JSON object member.');
            }
            $keys[$key] = true;

            $this->skipWhitespace($json);
            if (($json[$this->offset] ?? '') !== ':') {
                throw new \InvalidArgumentException('A JSON object member is missing its colon.');
            }
            $this->offset++;
            $this->parseValue($json);
            $this->skipWhitespace($json);

            $separator = $json[$this->offset] ?? '';
            if ($separator === '}') {
                $this->offset++;
                return;
            }
            if ($separator !== ',') {
                throw new \InvalidArgumentException('A JSON object member is missing its separator.');
            }
            $this->offset++;
        }
    }

    private function parseArray(string $json): void
    {
        $this->offset++;
        $this->skipWhitespace($json);

        if (($json[$this->offset] ?? '') === ']') {
            $this->offset++;
            return;
        }

        while (true) {
            $this->parseValue($json);
            $this->skipWhitespace($json);
            $separator = $json[$this->offset] ?? '';

            if ($separator === ']') {
                $this->offset++;
                return;
            }
            if ($separator !== ',') {
                throw new \InvalidArgumentException('A JSON array item is missing its separator.');
            }
            $this->offset++;
        }
    }

    /** Returns the complete quoted JSON string token. */
    private function parseString(string $json): string
    {
        $start = $this->offset++;
        while ($this->offset < $this->length) {
            $character = $json[$this->offset++];
            if ($character === '\\') {
                $this->offset++;
                continue;
            }
            if ($character === '"') {
                return substr($json, $start, $this->offset - $start);
            }
            if (ord($character) < 0x20) {
                throw new \InvalidArgumentException('A JSON string contains an unescaped control character.');
            }
        }

        throw new \InvalidArgumentException('The Registry response contains an unterminated JSON string.');
    }

    private function skipWhitespace(string $json): void
    {
        while (
            $this->offset < $this->length
            && str_contains(" \t\n\r", $json[$this->offset])
        ) {
            $this->offset++;
        }
    }
}
