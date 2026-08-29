<?php

declare(strict_types=1);

namespace App\Core\Tooling\Services;

use Symfony\Component\Finder\Finder;

/**
 * Reads Laravel log files without loading an entire file into memory.
 *
 * Laravel's default single-line-per-entry format is assumed
 * (a new entry starts with a line matching `[YYYY-MM-DD HH:MM:SS]`).
 * Multi-line entries (stack traces) are appended to the entry that
 * precedes them.
 */
final class LogReader
{
    private const LEVELS = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

    /**
     * List available log files under storage/logs, most recent first.
     *
     * @return array<int, array{filename: string, size: int, modified_at: string}>
     */
    public function listFiles(): array
    {
        $path = storage_path('logs');

        if (! is_dir($path)) {
            return [];
        }

        $finder = (new Finder())
            ->files()
            ->in($path)
            ->name('*.log')
            ->sortByModifiedTime()
            ->reverseSorting();

        $files = [];
        foreach ($finder as $file) {
            $files[] = [
                'filename' => $file->getFilename(),
                'size' => $file->getSize(),
                'modified_at' => date('c', $file->getMTime()),
            ];
        }

        return $files;
    }

    /**
     * Read parsed entries from a log file, filtered by level, paginated.
     *
     * @return array{entries: array<int, array{timestamp: string, level: string, message: string}>, total: int}
     */
    public function readEntries(string $filename, ?string $level, int $page, int $perPage): array
    {
        $path = $this->resolvePath($filename);

        $entries = $this->parseEntries($path);

        if ($level !== null) {
            $entries = array_values(array_filter(
                $entries,
                fn (array $entry): bool => $entry['level'] === strtolower($level),
            ));
        }

        // Most recent first
        $entries = array_reverse($entries);

        $total = count($entries);
        $offset = ($page - 1) * $perPage;

        return [
            'entries' => array_slice($entries, $offset, $perPage),
            'total' => $total,
        ];
    }

    /**
     * Resolve and validate a log filename against directory traversal.
     */
    private function resolvePath(string $filename): string
    {
        $safeFilename = basename($filename);
        $path = storage_path('logs' . DIRECTORY_SEPARATOR . $safeFilename);

        if (! is_file($path)) {
            abort(404, 'Log file not found.');
        }

        return $path;
    }

    /**
     * @return array<int, array{timestamp: string, level: string, message: string}>
     */
    private function parseEntries(string $path): array
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES) ?: [];
        $entries = [];
        $current = null;

        $levelPattern = implode('|', self::LEVELS);
        $headerPattern = '/^\[(?P<timestamp>\d{4}-\d{2}-\d{2}[^\]]+)\]\s+\S+\.(?P<level>' . $levelPattern . '):\s*(?P<message>.*)$/i';

        foreach ($lines as $line) {
            if (preg_match($headerPattern, $line, $matches) === 1) {
                if ($current !== null) {
                    $entries[] = $current;
                }

                $current = [
                    'timestamp' => $matches['timestamp'],
                    'level' => strtolower($matches['level']),
                    'message' => $matches['message'],
                ];

                continue;
            }

            if ($current !== null) {
                $current['message'] .= "\n" . $line;
            }
        }

        if ($current !== null) {
            $entries[] = $current;
        }

        return $entries;
    }
}
