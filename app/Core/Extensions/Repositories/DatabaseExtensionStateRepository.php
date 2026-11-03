<?php

declare(strict_types=1);

namespace App\Core\Extensions\Repositories;

use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Contracts\ExtensionStateRepositoryInterface;
use App\Core\Extensions\Enum\ExtensionStatus;
use App\Core\Extensions\State\ExtensionState;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Persists extension runtime state in the platform database.
 *
 * The manifest and PHP class remain part of the extension package; this table
 * stores only the runtime status and class identifier needed to restore it.
 */
final class DatabaseExtensionStateRepository implements ExtensionStateRepositoryInterface
{
    private const TABLE = 'extension_states';

    /** @return array<string, ExtensionState> */
    public function all(): array
    {
        $states = [];

        foreach (DB::table(self::TABLE)->orderBy('extension_id')->get() as $row) {
            $state = $this->createState((string) $row->extension_id, (string) $row->extension_class, (string) $row->status);

            if ($state !== null) {
                $states[(string) $row->extension_id] = $state;
            }
        }

        return $states;
    }

    public function find(string $id): ?ExtensionState
    {
        $row = DB::table(self::TABLE)->where('extension_id', $id)->first();

        return $row === null
            ? null
            : $this->createState((string) $row->extension_id, (string) $row->extension_class, (string) $row->status);
    }

    public function save(ExtensionState $state): void
    {
        $this->persist($state);
    }

    public function update(ExtensionState $state): void
    {
        $this->persist($state);
    }

    /** Persist a state using an atomic upsert keyed by the manifest ID. */
    private function persist(ExtensionState $state): void
    {
        $manifest = $state->extension->manifest();
        $now = now();

        DB::table(self::TABLE)->upsert(
            [[
                'extension_id' => $manifest->id,
                'extension_class' => $manifest->class,
                'status' => $state->status->value,
                'created_at' => $now,
                'updated_at' => $now,
            ]],
            ['extension_id'],
            ['extension_class', 'status', 'updated_at'],
        );
    }

    /** Restore a state only when its class is a valid platform extension. */
    private function createState(string $id, string $class, string $status): ?ExtensionState
    {
        $extensionStatus = ExtensionStatus::tryFrom($status);

        if (! class_exists($class) || $extensionStatus === null) {
            return null;
        }

        $extension = new $class();

        if (! $extension instanceof ExtensionInterface) {
            return null;
        }

        // Do not allow a stale row to bind an ID to a different extension package.
        if ($extension->manifest()->id !== $id) {
            return null;
        }

        return new ExtensionState($extension, $extensionStatus);
    }
}
