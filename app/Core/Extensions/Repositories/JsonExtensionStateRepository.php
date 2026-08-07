<?php

declare(strict_types=1);

namespace App\Core\Extensions\Repositories;

use App\Core\Extensions\Contracts\ExtensionStateRepositoryInterface;
use App\Core\Extensions\Enum\ExtensionStatus;
use App\Core\Extensions\State\ExtensionState;
use RuntimeException;

/**
 * Persists extension states in a JSON file.
 */
final class JsonExtensionStateRepository implements ExtensionStateRepositoryInterface
{
    /**
     * Create a new JSON extension state repository.
     */
    public function __construct(
        private readonly string $path,
    ) {
    }

    /**
     * Return all extension states.
     *
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

    /**
     * Return an extension state by its identifier.
     */
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
     */
    public function save(ExtensionState $state): void
    {
        $data = $this->read();

        $data[$state->extension->manifest()->id] = $this->serialize(
            $state,
        );

        $this->write($data);
    }

    /**
     * Update an extension state.
     */
    public function update(ExtensionState $state): void
    {
        $this->save($state);
    }

    /**
     * Read the JSON storage file.
     *
     * @return array<string, mixed>
     */
    private function read(): array
    {
        if (! is_file($this->path)) {
            return [];
        }

        $content = file_get_contents($this->path);

        if ($content === false || trim($content) === '') {
            return [];
        }

        $data = json_decode(
            $content,
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        if (! is_array($data)) {
            return [];
        }

        return $data;
    }

    /**
     * Write extension states to the JSON storage file.
     *
     * @param array<string, mixed> $data
     */
    private function write(array $data): void
    {
        $directory = dirname($this->path);

        if (! is_dir($directory) && ! mkdir(
            $directory,
            0755,
            true,
        ) && ! is_dir($directory)) {
            throw new RuntimeException(
                "Unable to create directory: {$directory}",
            );
        }

        $result = file_put_contents(
            $this->path,
            json_encode(
                $data,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
            ).PHP_EOL,
        );

        if ($result === false) {
            throw new RuntimeException(
                "Unable to write extension state file: {$this->path}",
            );
        }
    }

    /**
     * Serialize an extension state.
     *
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

    /**
     * Recreate an extension state from persisted data.
     *
     * @param mixed $data
     */
    private function createState(mixed $data): ?ExtensionState
    {
        if (! is_array($data)) {
            return null;
        }

        $class = $data['class'] ?? null;
        $status = $data['status'] ?? null;

        if (
            ! is_string($class)
            || ! class_exists($class)
            || ! is_string($status)
        ) {
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

        return new ExtensionState(
            $extension,
            $extensionStatus,
        );
    }
}
