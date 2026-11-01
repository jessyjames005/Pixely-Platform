<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use JsonException;
use Throwable;

/**
 * Lists, inspects, and optionally prunes persisted Pixely extension update reports.
 *
 * The command only operates on JSON report files in the configured reports directory.
 * It never modifies extension packages, installed extensions, or database state.
 */
final class ExtensionUpdateReportsCommand extends Command
{
    protected $signature = 'pixely:update:reports
        {--show= : Display one report by filename}
        {--limit=20 : Maximum number of reports to list}
        {--prune= : Delete reports older than this many days (requires --force)}\n        {--force : Confirm deletion of expired reports};

    protected $description = 'List, inspect, and prune Pixely extension update reports.';

    public function handle(): int
    {
        $directory = storage_path('app/extension-update-reports');
        File::ensureDirectoryExists($directory);

        $show = $this->option('show');
        if ($show !== null) {
            return $this->showReport($directory, (string) $show);
        }

        $prune = $this->option('prune');
        if ($prune !== null) {
            return $this->pruneReports($directory, (string) $prune);
        }

        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT);
        if ($limit === false || $limit < 1 || $limit > 500) {
            $this->components->error('--limit must be an integer between 1 and 500.');
            return self::INVALID;
        }

        $reports = $this->reportFiles($directory);
        if ($reports === []) {
            $this->components->info('No update reports found.');
            return self::SUCCESS;
        }

        $rows = [];
        foreach (array_slice($reports, 0, $limit) as $path) {
            try {
                $data = $this->readReport($path);
                $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
                $rows[] = [
                    basename($path),
                    (string) ($data['started_at'] ?? 'unknown'),
                    (string) ($data['mode'] ?? 'unknown'),
                    (string) ($summary['processed'] ?? 0),
                    (string) ($summary['failed'] ?? 0),
                    is_file($path) ? (string) filesize($path) : '0',
                ];
            } catch (Throwable $exception) {
                $rows[] = [basename($path), 'unreadable', 'invalid', '-', '-', '-'];
            }
        }

        $this->table(['Report', 'Started at', 'Mode', 'Processed', 'Failed', 'Bytes'], $rows);
        $this->line('Showing ' . count($rows) . ' of ' . count($reports) . ' report(s).');

        return self::SUCCESS;
    }

    private function showReport(string $directory, string $filename): int
    {
        // Accept only a plain filename, preventing traversal outside the reports directory.
        if ($filename === '' || basename($filename) !== $filename || ! str_ends_with(strtolower($filename), '.json')) {
            $this->components->error('--show must be a JSON report filename, not a path.');
            return self::INVALID;
        }

        $path = $directory . DIRECTORY_SEPARATOR . $filename;
        if (! is_file($path) || is_link($path)) {
            $this->components->error("Report [{$filename}] was not found.");
            return self::FAILURE;
        }

        try {
            $data = $this->readReport($path);
            $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->components->error("Report [{$filename}] is not valid JSON: {$exception->getMessage()}");
            return self::FAILURE;
        }
    }

    private function pruneReports(string $directory, string $daysOption): int
    {
        $days = filter_var($daysOption, FILTER_VALIDATE_INT);
        if ($days === false || $days < 1 || $days > 36500) {
            $this->components->error('--prune must be an integer between 1 and 36500 days.');
            return self::INVALID;
        }

        $cutoff = now()->subDays($days)->getTimestamp();
        $expired = array_values(array_filter(
            $this->reportFiles($directory),
            static fn (string $path): bool => filemtime($path) !== false && filemtime($path) < $cutoff,
        ));

        if ($expired === []) {
            $this->components->info("No reports older than {$days} day(s) found.");
            return self::SUCCESS;
        }

        $this->table(['Expired report'], array_map(static fn (string $path): array => [basename($path)], $expired));

        if (! $this->option('force')) {
            $this->components->warn('Nothing was deleted. Re-run with --force to confirm deletion.');
            return self::SUCCESS;
        }

        $deleted = 0;
        foreach ($expired as $path) {
            // Recheck path type immediately before deletion; never follow symbolic links.
            if (is_file($path) && ! is_link($path) && @unlink($path)) {
                $deleted++;
            }
        }

        $this->components->info("Deleted {$deleted} expired report(s).");
        return $deleted === count($expired) ? self::SUCCESS : self::FAILURE;
    }

    /** @return list<string> Newest reports first; symbolic links are excluded. */
    private function reportFiles(string $directory): array
    {
        $paths = array_values(array_filter(
            glob($directory . DIRECTORY_SEPARATOR . '*.json') ?: [],
            static fn (string $path): bool => is_file($path) && ! is_link($path),
        ));

        usort($paths, static fn (string $a, string $b): int => (filemtime($b) ?: 0) <=> (filemtime($a) ?: 0));
        return $paths;
    }

    /** @return array<string, mixed> */
    private function readReport(string $path): array
    {
        try {
            $contents = file_get_contents($path);
            if ($contents === false) {
                throw new \RuntimeException('Unable to read file.');
            }
            $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new \RuntimeException('Invalid JSON.', previous: $exception);
        }

        if (! is_array($data)) {
            throw new \RuntimeException('Report root must be a JSON object.');
        }

        return $data;
    }
}
