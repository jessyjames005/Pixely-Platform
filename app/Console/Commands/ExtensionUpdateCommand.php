<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Extensions\Installer\ExtensionInstaller;
use App\Core\Extensions\Installer\ExtensionPackageValidator;
use App\Core\Extensions\Manifest\ExtensionManifest;
use App\Core\Extensions\Update\ExtensionUpdatePlanner;
use App\Core\Extensions\Versioning\ExtensionVersionRepository;
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
        {--source= : Override the local update-package directory}';

    protected $description = 'Discover, validate and install available Pixely extension updates.';

    public function __construct(
        private readonly ExtensionPackageValidator $validator,
        private readonly ExtensionUpdatePlanner $planner,
        private readonly ExtensionVersionRepository $versions,
        private readonly ExtensionInstaller $installer,
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
        $seen = [];
        $processed = 0;
        $failed = 0;

        foreach ($packages as $packagePath) {
            try {
                $manifest = $this->readPackageManifest($packagePath);
                $id = $manifest['id'];

                if ($requestedId !== null && $id !== $requestedId) {
                    continue;
                }

                if (isset($seen[$id])) {
                    throw new \RuntimeException("More than one update package was found for extension [{$id}]. Keep only one candidate package.");
                }
                $seen[$id] = true;

                $from = $this->versions->find($id) ?? ($installed[$id] ?? null);
                if ($from === null) {
                    throw new \RuntimeException("Extension [{$id}] is not installed; this command only handles updates.");
                }

                $installed[$id] = $from;
                $candidate = new ExtensionManifest(
                    id: $id,
                    name: $manifest['name'],
                    version: $manifest['version'],
                    class: $manifest['class'],
                    path: $manifest['root'],
                    dependencies: $manifest['requires'],
                );

                $plan = $this->planner->plan(
                    $candidate,
                    $installed,
                    $manifest['require_versions'],
                    $manifest['conflicts'],
                    $dependencyGraph,
                );

                $processed++;
                $this->components->twoColumnDetail("{$id} ({$plan['from']} → {$plan['to']})", $this->option('dry-run') ? 'planned' : 'updating');

                if ($this->option('dry-run')) {
                    foreach ($plan['actions'] as $action) {
                        $this->line("  • {$action}");
                    }
                    continue;
                }

                $result = $this->installer->update($packagePath, $id, null);
                $installed[$id] = $result['version'];
                $this->components->info("Updated [{$id}] to version [{$result['version']}].");
            } catch (Throwable $exception) {
                $failed++;
                $this->components->error(basename($packagePath) . ': ' . $exception->getMessage());
            }
        }

        if ($requestedId !== null && $processed === 0 && $failed === 0) {
            $this->components->warn("No update package found for extension [{$requestedId}].");
        }

        $this->newLine();
        $this->line("Packages processed: {$processed}; failed: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Extract one candidate into a disposable directory and validate its manifest.
     * The installer repeats validation immediately before installation.
     *
     * @return array{root:string,id:string,name:string,version:string,class:string,requires:array,require_versions:array,conflicts:array}
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
                $graph[$manifest['id']] = array_values(array_filter($manifest['requires'], 'is_string'));
            }
        }

        return $graph;
    }
}
