<?php

declare(strict_types=1);

namespace App\Core\Tooling\Services;

/**
 * Validates that a raw SQL string is a single, safe, read-only SELECT.
 *
 * Defense in depth: even though the query runs against a database
 * user with SELECT-only grants, this validator rejects anything
 * that isn't unambiguously a single SELECT before it ever reaches
 * the database.
 */
final class ReadOnlyQueryValidator
{
    /**
     * Tables that must never be queryable through this tool,
     * even in read-only mode (credentials, sessions, tokens).
     */
    private const FORBIDDEN_TABLES = [
        'password_reset_tokens',
        'personal_access_tokens',
        'sessions',
    ];

    /**
     * Sensitive columns to strip from result sets, if present.
     */
    public const FORBIDDEN_COLUMNS = ['password', 'remember_token'];

    /**
     * @throws \InvalidArgumentException when the query is unsafe
     */
    public function assertSafe(string $sql): void
    {
        $trimmed = trim($sql);

        if ($trimmed === '') {
            throw new \InvalidArgumentException('The query cannot be empty.');
        }

        // Reject stacked statements: only a single trailing semicolon
        // (or none) is allowed, never one in the middle of the query.
        $withoutTrailingSemicolon = rtrim($trimmed, "; \t\n\r");
        if (str_contains($withoutTrailingSemicolon, ';')) {
            throw new \InvalidArgumentException('Only a single statement is allowed.');
        }

        // Must start with SELECT (case-insensitive), ignoring leading
        // whitespace and SQL comments used to disguise another verb.
        $withoutComments = preg_replace('/^(\s*--[^\n]*\n|\s*\/\*.*?\*\/)+/s', '', $withoutTrailingSemicolon) ?? '';
        $withoutComments = ltrim($withoutComments);

        if (! preg_match('/^select\s/i', $withoutComments)) {
            throw new \InvalidArgumentException('Only SELECT statements are allowed.');
        }

        // Reject any data-modifying or structural keyword appearing
        // anywhere in the query (e.g. inside a CTE or subquery).
        $forbiddenKeywords = [
            'insert', 'update', 'delete', 'drop', 'alter', 'truncate',
            'create', 'replace', 'grant', 'revoke', 'call', 'exec',
            'execute', 'into outfile', 'into dumpfile', 'load_file',
        ];

        foreach ($forbiddenKeywords as $keyword) {
            if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $withoutComments) === 1) {
                throw new \InvalidArgumentException("The query contains a forbidden keyword: {$keyword}.");
            }
        }

        foreach (self::FORBIDDEN_TABLES as $table) {
            if (preg_match('/\b' . preg_quote($table, '/') . '\b/i', $withoutComments) === 1) {
                throw new \InvalidArgumentException("Querying the '{$table}' table is not allowed.");
            }
        }
    }

    /**
     * Enforces a maximum row limit by appending or replacing LIMIT.
     */
    public function enforceLimit(string $sql, int $maxRows): string
    {
        $trimmed = rtrim(trim($sql), "; \t\n\r");

        if (preg_match('/\blimit\s+\d+(\s*,\s*\d+)?\s*$/i', $trimmed) === 1) {
            return preg_replace('/\d+(\s*,\s*\d+)?\s*$/', (string) $maxRows, $trimmed) ?? $trimmed;
        }

        return $trimmed . ' LIMIT ' . $maxRows;
    }

    /**
     * Strips sensitive columns from a result row, if present.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public function redactRow(array $row): array
    {
        foreach (self::FORBIDDEN_COLUMNS as $column) {
            unset($row[$column]);
        }

        return $row;
    }
}
