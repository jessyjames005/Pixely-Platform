<?php

declare(strict_types=1);

namespace App\Extensions\Translations\Services;

use App\Core\Extensions\Manager\ExtensionManager;
use App\Core\Extensions\Translations\ExtensionTranslatableInterface;
use Illuminate\Support\Arr;

/**
 * Discovers translatable modules (Core + extensions implementing
 * ExtensionTranslatableInterface) and reads/writes their lang files.
 *
 * Storage format: <module path>/lang/<locale>/<group>.php returning
 * a (possibly nested) associative array — the standard Laravel
 * PHP translation file format. Nested keys are flattened to dot
 * notation for editing (e.g. 'CAlert.one').
 */
final class TranslationRepository
{
    public function __construct(
        private readonly ExtensionManager $extensionManager,
    ) {}

    /**
     * @return array<string, string> module id => lang directory path
     */
    public function discoverModules(): array
    {
        $modules = ['core' => lang_path()];

        foreach ($this->extensionManager->all() as $extension) {
            if ($extension instanceof ExtensionTranslatableInterface) {
                $modules[$extension->manifest()->id] = $extension->translationsPath();
            }
        }

        return $modules;
    }

    /**
     * @return string[] locale codes available for a module (subfolder names)
     */
    public function availableLocales(string $modulePath): array
    {
        if (! is_dir($modulePath)) {
            return [];
        }

        return array_values(array_filter(
            scandir($modulePath) ?: [],
            fn(string $entry): bool => $entry !== '.' && $entry !== '..' && is_dir($modulePath . '/' . $entry),
        ));
    }

    /**
     * @return string[] group names (filenames without .php) available for a locale
     */
    public function availableGroups(string $modulePath, string $locale): array
    {
        $localePath = $modulePath . '/' . $this->safeSegment($locale);

        if (! is_dir($localePath)) {
            return [];
        }

        $files = glob($localePath . '/*.php') ?: [];

        return array_map(
            static fn(string $file): string => basename($file, '.php'),
            $files,
        );
    }

    /**
     * @return array<string, string> flattened dot-notation key => value
     */
    public function readGroup(string $modulePath, string $locale, string $group): array
    {
        $path = $this->groupFilePath($modulePath, $locale, $group);

        if (! is_file($path)) {
            return [];
        }

        // Ensure PHP does not return stale metadata after the translation file
        // has been replaced atomically by writeGroup().
        clearstatcache(true, $path);

        // Ensure OPcache does not serve the previous version of the PHP file.
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path, true);
        }

        /** @var array<string, mixed> $data */
        $data = include $path;

        return Arr::dot($data);
    }

    /**
     * @param array<string, string> $translations flattened dot-notation key => value
     */
    public function writeGroup(string $modulePath, string $locale, string $group, array $translations): void
    {
        $localePath = $modulePath . '/' . $this->safeSegment($locale);

        if (! is_dir($localePath)) {
            mkdir($localePath, 0755, true);
        }

        // Merge with the existing group rather than replacing it wholesale,
        // so a partial save (e.g. editing one key) never silently drops
        // other translations already present in the file.
        $existing = $this->readGroup($modulePath, $locale, $group);
        $merged = array_merge($existing, $translations);

        $nested = Arr::undot($merged);
        $export = var_export($nested, true);

        $contents = "<?php\n\ndeclare(strict_types=1);\n\nreturn {$export};\n";
        $path = $this->groupFilePath($modulePath, $locale, $group);

        file_put_contents($path, $contents);

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path, true);
        }
    }

    /**
     * Compares a target locale's group against a reference locale's
     * group, returning every key (union of both), each value, and
     * whether it looks suspect (missing or identical to the key).
     *
     * @return array{entries: array<int, array{key: string, reference: ?string, target: ?string, suspect: bool}>, completion: float}
     */
    public function compare(string $modulePath, string $referenceLocale, string $targetLocale, string $group): array
    {
        $reference = $this->readGroup($modulePath, $referenceLocale, $group);
        $target = $this->readGroup($modulePath, $targetLocale, $group);

        $keys = array_unique([...array_keys($reference), ...array_keys($target)]);
        sort($keys);

        $entries = [];
        $filled = 0;

        foreach ($keys as $key) {
            $referenceValue = $reference[$key] ?? null;
            $targetValue = $target[$key] ?? null;

            $suspect = $targetValue === null || $targetValue === '' || $targetValue === $key;

            if (! $suspect) {
                $filled++;
            }

            $entries[] = [
                'key' => $key,
                'reference' => $referenceValue,
                'target' => $targetValue,
                'suspect' => $suspect,
            ];
        }

        $total = count($keys);

        return [
            'entries' => $entries,
            'completion' => $total > 0 ? round(($filled / $total) * 100, 3) : 100.0,
        ];
    }

    private function groupFilePath(string $modulePath, string $locale, string $group): string
    {
        return $modulePath . '/' . $this->safeSegment($locale) . '/' . $this->safeSegment($group) . '.php';
    }

    /**
     * Prevents path traversal through the locale/group route segments.
     */
    private function safeSegment(string $segment): string
    {
        $safe = basename($segment);

        if ($safe === '' || $safe !== $segment) {
            abort(422, 'Invalid locale or group.');
        }

        return $safe;
    }
}
