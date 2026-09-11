<?php

declare(strict_types=1);

namespace App\Core\Extensions\Repositories;

use App\Core\Extensions\Contracts\ExtensionStateRepositoryInterface;
use App\Core\Extensions\Enum\ExtensionStatus;
use App\Core\Extensions\State\ExtensionState;
use RuntimeException;

/**
 * Persists extension states in a JSON file.
 *
 * Reads and writes are serialized through an exclusive file lock
 * held for the entire read-modify-write cycle, and writes are
 * atomic (write to a temp file, then rename over the target).
 * Without this, concurrent requests (e.g. two admin tabs, or an
 * enable/disable racing a Kernel boot reading the same file) can
 * interleave their writes and corrupt the file — this happened
 * repeatedly before this fix.
 */
final class JsonExtensionStateRepository implements ExtensionStateRepositoryInterface
{
    public function __construct(
        private readonly string $path,
    ) {
    }

    /**
     * @return array<string, ExtensionState>
     */
    public function all(): array
    {
        $data = $this->read();

        $states = [];

        foreach ($data as $id => $extension) {
            $state = $this->createState($extension);

            if ($state !== null) {
                $states[$id] = $state;
            }
        }

        return $states;
    }

    public function find(string $id): ?ExtensionState
    {
        $data = $this->read();

        if (! isset($data[$id])) {
            return null;
        }

        return $this->createState($data[$id]);
    }

    /**
     * Persist an extension state.
     *
     * Reads the current file, applies the change, and writes back —
     * all while holding an exclusive lock, so a concurrent save()
     * from another request cannot interleave with this one.
     */
    public function save(ExtensionState $state): void
    {
        $this->withExclusiveLock(function (array $data) use ($state): array {
            $data[$state->extension->manifest()->id] = $this->serialize($state);

            return $data;
        });
    }

    public function update(ExtensionState $state): void
    {
        $this->save($state);
    }

    /**
     * Opens the file (creating it if needed), takes an exclusive
     * lock, reads+decodes the current content, lets the callback
     * compute the new content, then writes it back and releases
     * the lock — read, modify, and write all happen under one lock
     * so no other process can interleave with this operation.
     *
     * @param callable(array<string, mixed>): array<string, mixed> $mutator
     */
    private function withExclusiveLock(callable $mutator): void
    {
        $directory = dirname($this->path);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException("Unable to create directory: {$directory}");
        }

        // 'c+' creates the file if missing, without truncating it,
        // and allows both reading and writing on the same handle.
        $handle = fopen($this->path, 'c+');

        if ($handle === false) {
            throw new RuntimeException("Unable to open extension state file: {$this->path}");
        }

        try {
            if (! flock($handle, LOCK_EX)) {
                throw new RuntimeException("Unable to lock extension state file: {$this->path}");
            }

            $content = stream_get_contents($handle);
            $data = $this->decode($content === false ? '' : $content);

            $updated = $mutator($data);

            $encoded = json_encode($updated, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, $encoded);
            fflush($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function read(): array
    {
        if (! is_file($this->path)) {
            return [];
        }

        $handle = fopen($this->path, 'r');

        if ($handle === false) {
            return [];
        }

        try {
            // A shared lock still serializes against an in-progress
            // exclusive write, so a read never observes a half-written file.
            flock($handle, LOCK_SH);
            $content = stream_get_contents($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }

        return $this->decode($content === false ? '' : $content);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $content): array
    {
        if (trim($content) === '') {
            return [];
        }

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            // A file that somehow still ends up malformed (e.g. from
            // before this fix) is treated as empty rather than
            // crashing the whole platform boot.
            return [];
        }

        return is_array($data) ? $data : [];
    }

    /**
     * @return array<string, string>
     */
    private function serialize(ExtensionState $state): array
    {
        $manifest = $state->extension->manifest();

        return [
            'id' => $manifest->id,
            'name' => $manifest->name,
            'version' => $manifest->version,
            'class' => $manifest->class,
            'status' => $state->status->value,
        ];
    }

    private function createState(mixed $data): ?ExtensionState
    {
        if (! is_array($data)) {
            return null;
        }

        $class = $data['class'] ?? null;
        $status = $data['status'] ?? null;

        if (! is_string($class) || ! class_exists($class) || ! is_string($status)) {
            return null;
        }

        $extension = new $class();

        if (! $extension instanceof \App\Core\Extensions\Contracts\ExtensionInterface) {
            return null;
        }

        $extensionStatus = ExtensionStatus::tryFrom($status);

        if ($extensionStatus === null) {
            return null;
        }

        return new ExtensionState($extension, $extensionStatus);
    }
}
