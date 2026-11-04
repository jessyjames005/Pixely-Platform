<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Extensions\Installer\ExtensionInstallerInterface;
use App\Core\Extensions\Installer\ExtensionPackageValidator;
use App\Core\Extensions\Manifest\ExtensionManifest;
use App\Core\Extensions\Update\ExtensionUpdatePlanner;
use App\Core\Extensions\Versioning\ExtensionVersionRepository;
use App\Core\Extensions\Update\ExtensionUpdateReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Discovers locally supplied extension update archives and coordinates their
 * validation, planning and installation through the existing installer.
 *
 * Update packages are intentionally sourced from storage/app/extension-updates;
 * remote repositories are not assumed until a repository contract exists.
 */
final class ExtensionUpdateCommand extends Command
{
    protected $signature = 'pixely:update
        {--extension= : Update only the extension with this manifest id}
        {--dry-run : Validate and display the update plan without changing live files}
        {--source= : Override the local update-package directory}
        {--report= : Override the JSON execution report path}
        {--yes : Explicitly approve updates without an interactive prompt}';

    protected $description = 'Discover, validate and install available Pixely extension updates.';

    public function __construct(
        private readonly ExtensionPackageValidator $validator,
        private readonly ExtensionUpdatePlanner $planner,
        private readonly ExtensionVersionRepository $versions,
        private readonly ExtensionInstallerInterface $installer,
        private readonly ExtensionUpdateReport $reporter,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $source = (string) ($this->option('source') ?: storage_path('app/extension-updates'));
        $requestedId = $this->option('extension');

        if ($requestedId !== null && preg_match('/^[a-z0-9-]+$/', (string) $requestedId) !== 1) {
            $this->components->error('The --extension value must be a valid extension id.');

            return self::INVALID;
        }

        if (! is_dir($source)) {
            $this->components->info("No update directory found at [{$source}]. Create it and add extension ZIP packages.");

            return self::SUCCESS;
        }

        $packages = glob(rtrim($source, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.zip') ?: [];
        sort($packages, SORT_NATURAL | SORT_FLAG_CASE);

        if ($packages === []) {
            $this->components->info("No extension update packages found in [{$source}].");

            return self::SUCCESS;
        }

        $installed = $this->discoverInstalledVersions();
        $dependencyGraph = $this->discoverInstalledDependencyGraph();
        $startedAt = now()->toIso8601String();

        if ($this->option('dry-run')) {
            return $this->handleDryRun($packages, $requestedId, $source, $installed, $dependencyGraph, $startedAt);
        }

        return $this->handleApply($packages, $requestedId, $source, $installed, $dependencyGraph, $startedAt);
    }

    /**
     * Plan and apply selected updates in dependency-first order after operator approval.
     *
     * @param string[] $packages
     * @param array<string, string> $installed
     * @param array<string, string[]> $dependencyGraph
     */
    private function handleApply(
        array $packages,
        ?string $requestedId,
        string $source,
        array $installed,
        array $dependencyGraph,
        string $startedAt,
    ): int {
        $candidates = [];
        $packagePaths = [];
        $reportItems = [];
        $seen = [];
        $processed = 0;
        $updated = 0;
        $failed = 0;
        $blocked = 0;

        foreach ($packages as $packagePath) {
            $id = null;

            try {
                $manifest = $this->readPackageManifest($packagePath);
                $id = $manifest['id'];

                if ($requestedId !== null && $id !== $requestedId) {
                    continue;
                }

                if (isset($seen[$id])) {
                    throw new \RuntimeException(
                        "More than one update package was found for extension [{$id}]. Keep only one candidate package.",
                    );
                }
                $seen[$id] = true;

                $from = $this->versions->find($id) ?? ($installed[$id] ?? null);
                if ($from === null) {
                    throw new \RuntimeException(
                        "Extension [{$id}] is not installed; this command only handles updates.",
                    );
                }

                $installed[$id] = $from;
                $candidates[] = [
                    'manifest' => new ExtensionManifest(
                        id: $id,
                        name: $manifest['name'],
                        version: $manifest['version'],
                        minimum_kernel_version: $manifest['minimum_kernel_version'],
                        class: $manifest['class'],
                        path: $manifest['root'],
                        dependencies: $manifest['requires'],
                    ),
                    'required_versions' => $manifest['require_versions'],
                    'conflicts' => $manifest['conflicts'],
                ];
                $packagePaths[$id] = $packagePath;
            } catch (Throwable $exception) {
                $failed++;
                $message = $exception->getMessage();
                $reportItems[] = [
                    'package' => basename($packagePath),
                    'extension' => $id,
                    'status' => 'failed',
                    'message' => $message,
                ];
                $this->components->error(basename($packagePath) . ': ' . $message);
            }
        }

        if ($requestedId !== null && $candidates === [] && $failed === 0) {
            $this->components->warn("No update package found for extension [{$requestedId}].");
        }

        $plans = [];
        $executionOrder = [];

        if ($candidates !== []) {
            try {
                $plans = $this->planner->planBatch($candidates, $installed, $dependencyGraph);
                $executionOrder = array_column($plans, 'extension');
                $this->line('Execution order: ' . implode(' → ', $executionOrder));

                foreach ($plans as $plan) {
                    $this->components->twoColumnDetail(
                        "{$plan['extension']} ({$plan['from']} → {$plan['to']})",
                        'planned',
                    );
                }
            } catch (Throwable $exception) {
                $message = $exception->getMessage();
                $this->components->error('Batch update plan blocked: ' . $message);

                foreach ($candidates as $candidate) {
                    $id = $candidate['manifest']->id;
                    $failed++;
                    $reportItems[] = [
                        'package' => basename($packagePaths[$id]),
                        'extension' => $id,
                        'status' => 'failed',
                        'message' => $message,
                    ];
                }
            }
        }

        $exitCode = self::SUCCESS;
        if ($plans !== []) {
            $this->components->warn(
                'Updates run sequentially without a global transaction or a guaranteed database rollback. '
                . 'Ensure you have a database backup before proceeding.',
            );
            $approved = (bool) $this->option('yes');

            if (! $approved) {
                if (! $this->input->isInteractive()) {
                    $message = 'Use --yes to explicitly approve updates in a non-interactive environment.';
                    $this->components->error($message);
                    $exitCode = self::FAILURE;

                    foreach ($plans as $plan) {
                        $reportItems[] = [
                            'extension' => $plan['extension'],
                            'from' => $plan['from'],
                            'to' => $plan['to'],
                            'status' => 'approval_required',
                            'message' => $message,
                        ];
                    }
                } else {
                    $approved = $this->confirm('Proceed with the dependency-first extension update plan?', false);
                    if (! $approved) {
                        $this->components->info('Update cancelled; no extension files were changed.');

                        foreach ($plans as $plan) {
                            $reportItems[] = [
                                'extension' => $plan['extension'],
                                'from' => $plan['from'],
                                'to' => $plan['to'],
                                'status' => 'cancelled',
                                'message' => 'Operator declined the update plan.',
                            ];
                        }
                    }
                }
            }

            if ($approved) {
                $candidateIds = array_fill_keys(array_keys($packagePaths), true);
                $statuses = [];

                foreach ($plans as $plan) {
                    $id = $plan['extension'];
                    $blockedDependencies = [];

                    foreach (array_keys($plan['dependencies']) as $dependencyId) {
                        if (
                            isset($candidateIds[$dependencyId])
                            && ($statuses[$dependencyId] ?? null) !== 'updated'
                        ) {
                            $blockedDependencies[] = $dependencyId;
                        }
                    }

                    if ($blockedDependencies !== []) {
                        $message = 'Blocked because update dependency or dependencies did not complete successfully: '
                            . implode(', ', $blockedDependencies) . '.';
                        $statuses[$id] = 'blocked';
                        $blocked++;
                        $reportItems[] = [
                            'extension' => $id,
                            'from' => $plan['from'],
                            'to' => $plan['to'],
                            'status' => 'blocked',
                            'message' => $message,
                        ];
                        $this->components->error("Blocked [{$id}]: {$message}");
                        continue;
                    }

                    $processed++;
                    try {
                        $result = $this->installer->update($packagePaths[$id], $id, null);
                        $installed[$id] = $result['version'];
                        $statuses[$id] = 'updated';
                        $updated++;
                        $reportItems[] = [
                            'extension' => $id,
                            'from' => $plan['from'],
                            'to' => $result['version'],
                            'status' => 'updated',
                            'message' => 'Update completed successfully.',
                        ];
                        $this->components->info("Updated [{$id}] to version [{$result['version']}].");
                    } catch (Throwable $exception) {
                        $statuses[$id] = 'failed';
                        $failed++;
                        $reportItems[] = [
                            'extension' => $id,
                            'from' => $plan['from'],
                            'to' => $plan['to'],
                            'status' => 'failed',
                            'message' => $exception->getMessage(),
                        ];
                        $this->components->error("Update failed for [{$id}]: " . $exception->getMessage());
                    }
                }
            }
        }

        if ($failed > 0 || $blocked > 0) {
            $exitCode = self::FAILURE;
        }

        $this->newLine();
        $reportPath = (string) ($this->option('report') ?: storage_path(
            'app/extension-update-reports/' . now()->format('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.json',
        ));

        try {
            $savedPath = $this->reporter->write($reportPath, [
                'schema_version' => 1,
                'command' => 'pixely:update',
                'started_at' => $startedAt,
                'finished_at' => now()->toIso8601String(),
                'mode' => 'apply',
                'source' => $source,
                'filter' => $requestedId,
                'execution_order' => $executionOrder,
                'summary' => [
                    'processed' => $processed,
                    'updated' => $updated,
                    'failed' => $failed,
                    'blocked' => $blocked,
                    'exit_code' => $exitCode,
                ],
                'limitations' => [
                    'Updates are sequential; there is no global transaction or guaranteed database rollback.',
                ],
                'items' => $reportItems,
            ]);
            $this->components->info("Execution report: {$savedPath}");
        } catch (Throwable $exception) {
            $this->components->error('Could not write execution report: ' . $exception->getMessage());

            return self::FAILURE;
        }

        $this->line(
            "Packages processed: {$processed}; updated: {$updated}; failed: {$failed}; blocked: {$blocked}.",
        );

        return $exitCode;
    }

    /**
     * Plan all selected local candidates together without invoking the installer.
     *
     * @param string[] $packages
     * @param array<string, string> $installed
     * @param array<string, string[]> $dependencyGraph
     */
    private function handleDryRun(
        array $packages,
        ?string $requestedId,
        string $source,
        array $installed,
        array $dependencyGraph,
        string $startedAt,
    ): int {
        $candidates = [];
        $packagePaths = [];
        $reportItems = [];
        $failed = 0;

        foreach ($packages as $packagePath) {
            $id = null;

            try {
                $manifest = $this->readPackageManifest($packagePath);
                $id = $manifest['id'];

                if ($requestedId !== null && $id !== $requestedId) {
                    continue;
                }

                $from = $this->versions->find($id) ?? ($installed[$id] ?? null);
                if ($from === null) {
                    throw new \RuntimeException(
                        "Extension [{$id}] is not installed; this command only handles updates.",
                    );
                }

                $installed[$id] = $from;
                $candidates[] = [
                    'manifest' => new ExtensionManifest(
                        id: $id,
                        name: $manifest['name'],
                        version: $manifest['version'],
                        minimum_kernel_version: $manifest['minimum_kernel_version'],
                        class: $manifest['class'],
                        path: $manifest['root'],
                        dependencies: $manifest['requires'],
                    ),
                    'required_versions' => $manifest['require_versions'],
                    'conflicts' => $manifest['conflicts'],
                ];
                $packagePaths[$id][] = $packagePath;
            } catch (Throwable $exception) {
                $failed++;
                $message = $exception->getMessage();
                $reportItems[] = [
                    'package' => basename($packagePath),
                    'extension' => $id,
                    'status' => 'failed',
                    'message' => $message,
                ];
                $this->components->error(basename($packagePath) . ': ' . $message);
            }
        }

        $plans = [];
        $executionOrder = [];

        if ($candidates !== []) {
            try {
                $plans = $this->planner->planBatch($candidates, $installed, $dependencyGraph);
                $executionOrder = array_column($plans, 'extension');
                $this->line('Execution order: ' . implode(' → ', $executionOrder));

                foreach ($plans as $plan) {
                    $id = $plan['extension'];
                    $this->components->twoColumnDetail("{$id} ({$plan['from']} → {$plan['to']})", 'planned');
                    foreach ($plan['actions'] as $action) {
                        $this->line("  • {$action}");
                    }

                    $reportItems[] = [
                        'extension' => $id,
                        'from' => $plan['from'],
                        'to' => $plan['to'],
                        'status' => 'planned',
                        'message' => null,
                    ];
                }
            } catch (Throwable $exception) {
                $message = $exception->getMessage();
                $this->components->error('Batch update plan blocked: ' . $message);

                foreach ($packagePaths as $id => $paths) {
                    foreach ($paths as $packagePath) {
                        $failed++;
                        $reportItems[] = [
                            'package' => basename($packagePath),
                            'extension' => $id,
                            'status' => 'failed',
                            'message' => $message,
                        ];
                    }
                }
            }
        }

        if ($requestedId !== null && $plans === [] && $failed === 0) {
            $this->components->warn("No update package found for extension [{$requestedId}].");
        }

        $this->newLine();
        $exitCode = $failed > 0 ? self::FAILURE : self::SUCCESS;
        $reportPath = (string) ($this->option('report') ?: storage_path(
            'app/extension-update-reports/' . now()->format('Ymd-His') . '-'
                . bin2hex(random_bytes(3)) . '.json',
        ));

        try {
            $savedPath = $this->reporter->write($reportPath, [
                'schema_version' => 1,
                'command' => 'pixely:update',
                'started_at' => $startedAt,
                'finished_at' => now()->toIso8601String(),
                'mode' => 'dry-run',
                'source' => $source,
                'filter' => $requestedId,
                'summary' => ['processed' => count($plans), 'failed' => $failed, 'exit_code' => $exitCode],
                'execution_order' => $executionOrder,
                'items' => $reportItems,
            ]);
            $this->components->info("Execution report: {$savedPath}");
        } catch (Throwable $exception) {
            $this->components->error('Could not write execution report: ' . $exception->getMessage());

            return self::FAILURE;
        }

        $this->line('Packages processed: ' . count($plans) . "; failed: {$failed}.");

        return $exitCode;
    }

    /**
     * Extract one candidate into a disposable directory and validate its manifest.
     * The installer repeats validation immediately before installation.
     *
     * @return array{
     *     root: string,
     *     id: string,
     *     name: string,
     *     version: string,
     *     minimum_kernel_version: string,
     *     class: string,
     *     requires: array,
     *     require_versions: array,
     *     conflicts: array
     * }
     */
    private function readPackageManifest(string $packagePath): array
    {
        $staging = storage_path('app/extension-update-inspection/' . bin2hex(random_bytes(8)));
        File::ensureDirectoryExists($staging);

        try {
            $this->validator->extractSafely($packagePath, $staging);

            return $this->validator->locateAndValidateManifest($staging);
        } finally {
            File::deleteDirectory($staging);
        }
    }

    /** @return array<string,string> */
    private function discoverInstalledVersions(): array
    {
        $versions = [];
        $base = base_path('app/Extensions');

        foreach (File::directories($base) as $directory) {
            $manifestPath = $directory . DIRECTORY_SEPARATOR . 'extension.php';
            if (! is_file($manifestPath)) {
                continue;
            }

            $manifest = include $manifestPath;
            if (is_array($manifest) && is_string($manifest['id'] ?? null) && is_string($manifest['version'] ?? null)) {
                $versions[$manifest['id']] = $manifest['version'];
            }
        }

        return $versions;
    }

    /** @return array<string,string[]> */
    private function discoverInstalledDependencyGraph(): array
    {
        $graph = [];
        $base = base_path('app/Extensions');

        foreach (File::directories($base) as $directory) {
            $manifestPath = $directory . DIRECTORY_SEPARATOR . 'extension.php';
            if (! is_file($manifestPath)) {
                continue;
            }

            $manifest = include $manifestPath;
            if (is_array($manifest) && is_string($manifest['id'] ?? null) && is_array($manifest['requires'] ?? [])) {
                $graph[$manifest['id']] = array_values(array_filter($manifest['requires'] ?? [], 'is_string'));
            }
        }

        return $graph;
    }
}
