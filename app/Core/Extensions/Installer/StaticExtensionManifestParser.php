<?php

declare(strict_types=1);

namespace App\Core\Extensions\Installer;

use RuntimeException;
use Throwable;

/**
 * Parses the literal subset used by extension manifests without executing PHP.
 */
final class StaticExtensionManifestParser
{
    /** @return array<string|int, mixed> */
    public function parseFile(string $path): array
    {
        $source = file_get_contents($path);
        if ($source === false) {
            throw new RuntimeException('Unable to read extension.php.');
        }

        if (! str_starts_with(ltrim($source), '<?php')) {
            $source = "<?php\n" . $source;
        }

        try {
            $tokens = $this->significantTokens(token_get_all($source, TOKEN_PARSE));
        } catch (Throwable $exception) {
            throw new RuntimeException('extension.php is not valid static manifest syntax.', previous: $exception);
        }

        $position = 0;
        if ($this->isToken($tokens, $position, T_OPEN_TAG)) {
            $position++;
        }

        if ($this->isToken($tokens, $position, T_DECLARE)) {
            $this->parseStrictTypesDeclaration($tokens, $position);
        }

        if (! $this->isToken($tokens, $position, T_RETURN)) {
            throw new RuntimeException('extension.php must contain a single literal return array.');
        }
        $position++;

        $manifest = $this->parseArray($tokens, $position);
        if ($this->text($tokens, $position) !== ';') {
            throw new RuntimeException('extension.php must end its return array with a semicolon.');
        }
        $position++;

        if ($position !== count($tokens)) {
            throw new RuntimeException('Executable or unsupported content follows the extension manifest.');
        }

        return $manifest;
    }

    /**
     * @param array<int, array{id: int|string, text: string}> $tokens
     */
    private function parseStrictTypesDeclaration(array $tokens, int &$position): void
    {
        $position++;
        $expected = [
            '(',
            'strict_types',
            '=',
            '1',
            ')',
            ';',
        ];

        foreach ($expected as $token) {
            if ($this->text($tokens, $position) !== $token) {
                throw new RuntimeException('Only declare(strict_types=1) is allowed in extension.php.');
            }
            $position++;
        }
    }

    /**
     * @param array<int, array{id: int|string, text: string}> $tokens
     * @return array<string|int, mixed>
     */
    private function parseArray(array $tokens, int &$position): array
    {
        if ($this->text($tokens, $position) !== '[') {
            throw new RuntimeException('The extension manifest return value must be a literal array.');
        }
        $position++;
        $result = [];

        while ($this->text($tokens, $position) !== ']') {
            if (! isset($tokens[$position])) {
                throw new RuntimeException('Unclosed array in extension.php.');
            }

            $value = $this->parseValue($tokens, $position);
            if ($this->text($tokens, $position) === '=>') {
                if (! is_string($value) && ! is_int($value)) {
                    throw new RuntimeException('Manifest array keys must be literal strings or integers.');
                }
                $position++;
                $result[$value] = $this->parseValue($tokens, $position);
            } else {
                $result[] = $value;
            }

            if ($this->text($tokens, $position) === ',') {
                $position++;
                continue;
            }
            if ($this->text($tokens, $position) !== ']') {
                throw new RuntimeException('Unsupported expression or missing comma in extension.php.');
            }
        }

        $position++;

        return $result;
    }

    /**
     * @param array<int, array{id: int|string, text: string}> $tokens
     */
    private function parseValue(array $tokens, int &$position): mixed
    {
        $token = $tokens[$position] ?? null;
        if ($token === null) {
            throw new RuntimeException('Unexpected end of extension.php.');
        }

        if ($token['text'] === '[') {
            return $this->parseArray($tokens, $position);
        }

        if ($token['id'] === T_CONSTANT_ENCAPSED_STRING) {
            $position++;

            return $this->decodeString($token['text']);
        }

        if (in_array($token['id'], [T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
            $class = ltrim($token['text'], '\\');
            if (
                preg_match('/^[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)+$/', $class) !== 1
                || $this->text($tokens, $position + 1) !== '::'
                || strtolower((string) $this->text($tokens, $position + 2)) !== 'class'
            ) {
                throw new RuntimeException('Class metadata must use a qualified class name ending in ::class.');
            }

            $position += 3;

            return $class;
        }

        if ($token['id'] === T_LNUMBER && preg_match('/^\d+$/', $token['text']) === 1) {
            $position++;

            return (int) $token['text'];
        }

        if ($token['id'] === T_STRING) {
            $position++;

            return match (strtolower($token['text'])) {
                'true' => true,
                'false' => false,
                'null' => null,
                default => throw new RuntimeException('Unsupported identifier in extension.php.'),
            };
        }

        throw new RuntimeException('Unsupported expression in extension.php; only literal values are allowed.');
    }

    private function decodeString(string $token): string
    {
        $quote = $token[0];
        $value = substr($token, 1, -1);
        if ($quote === '"') {
            return stripcslashes($value);
        }

        return preg_replace_callback(
            '/\\\\([\\\\\'])/',
            static fn (array $matches): string => $matches[1],
            $value,
        ) ?? $value;
    }

    /**
     * @param list<array{int, string, int}|string> $tokens
     * @return array<int, array{id: int|string, text: string}>
     */
    private function significantTokens(array $tokens): array
    {
        $significant = [];
        foreach ($tokens as $token) {
            if (is_array($token)) {
                if (in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }
                if ($token[0] === T_INLINE_HTML && trim($token[1]) === '') {
                    continue;
                }
                $significant[] = ['id' => $token[0], 'text' => $token[1]];
                continue;
            }
            $significant[] = ['id' => $token, 'text' => $token];
        }

        return $significant;
    }

    /**
     * @param array<int, array{id: int|string, text: string}> $tokens
     */
    private function isToken(array $tokens, int $position, int $id): bool
    {
        return isset($tokens[$position]) && $tokens[$position]['id'] === $id;
    }

    /**
     * @param array<int, array{id: int|string, text: string}> $tokens
     */
    private function text(array $tokens, int $position): ?string
    {
        return $tokens[$position]['text'] ?? null;
    }
}
