<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Extensions\Update\ExtensionUpdateReport;
use App\Core\Updates\Enums\UpdateChannel;
use App\Core\Updates\Services\ExtensionUpdateDiscovery;
use Illuminate\Console\Command;
use Throwable;

/**
 * Shows read-only extension update plans from the official Registry.
 */
final class ExtensionUpdatesCommand extends Command
{
    protected $signature = 'pixely:updates
        {--extension= : Check only the installed extension with this id}
        {--channel=stable : Release channel: stable, beta, or dev}';

    protected $description = 'Discover compatible extension updates without changing installed files.';

    public function __construct(
        private readonly ExtensionUpdateDiscovery $discovery,
        private readonly ExtensionUpdateReport $reporter,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $requestedId = $this->option('extension');
        if ($requestedId !== null && preg_match('/^[a-z0-9-]+$/', (string) $requestedId) !== 1) {
            $this->components->error('The --extension value must be a valid extension id.');

            return self::INVALID;
        }

        $channel = UpdateChannel::tryFrom((string) $this->option('channel'));
        if ($channel === null) {
            $this->components->error('--channel must be one of: stable, beta, or dev.');

            return self::INVALID;
        }

        $startedAt = now()->toIso8601String();
        $failed = 0;
        $items = [];
        $processed = 0;

        try {
            if ($requestedId === null) {
                $results = $this->discovery->checkAll($channel);
            } else {
                $result = $this->discovery->check((string) $requestedId, $channel);
                if ($result === null) {
                    throw new \RuntimeException("Extension [{$requestedId}] is not installed.");
                }
                $results = [$result];
            }

            foreach ($results as $result) {
                $processed++;
                $candidate = $result->candidate;
                $item = [
                    'extension' => $result->installed->id,
                    'name' => $result->installed->name,
                    'from' => $result->installed->version,
                    'to' => $candidate?->version,
                    'channel' => $channel->value,
                    'status' => $candidate === null ? 'no-update' : 'planned',
                    'reason' => $candidate === null
                        ? $result->reason
                        : 'Compatible update available; no files were changed.',
                ];
                $items[] = $item;

                $this->components->twoColumnDetail(
                    "{$item['extension']} ({$item['from']} → " . ($item['to'] ?? 'no update') . ')',
                    $item['status'],
                );
                if ($item['reason'] !== null) {
                    $this->line("  {$item['reason']}");
                }
            }
        } catch (Throwable $exception) {
            $failed = 1;
            $this->components->error('Update discovery failed: ' . $exception->getMessage());
            $items[] = [
                'extension' => $requestedId,
                'status' => 'failed',
                'reason' => $exception->getMessage(),
            ];
        }

        $exitCode = $failed > 0 ? self::FAILURE : self::SUCCESS;
        $reportPath = storage_path(
            'app/extension-update-reports/' . now()->format('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.json',
        );

        try {
            $savedPath = $this->reporter->write($reportPath, [
                'schema_version' => 1,
                'command' => 'pixely:updates',
                'started_at' => $startedAt,
                'finished_at' => now()->toIso8601String(),
                'mode' => 'dry-run',
                'source' => 'official-registry',
                'filter' => $requestedId,
                'channel' => $channel->value,
                'summary' => ['processed' => $processed, 'failed' => $failed, 'exit_code' => $exitCode],
                'items' => $items,
            ]);
            $this->components->info("Update plan report: {$savedPath}");
        } catch (Throwable $exception) {
            $this->components->error('Could not write update plan report: ' . $exception->getMessage());

            return self::FAILURE;
        }

        $this->line("Extensions checked: {$processed}; failed: {$failed}. No files were changed.");

        return $exitCode;
    }
}
