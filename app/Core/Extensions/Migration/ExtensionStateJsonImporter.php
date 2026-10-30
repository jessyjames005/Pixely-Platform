<?php

declare(strict_types=1);

namespace App\Core\Extensions\Migration;

use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Enum\ExtensionStatus;
use Illuminate\Support\Facades\DB;
use JsonException;

/** Imports legacy JSON state once, before the Kernel registers new extensions. */
final class ExtensionStateJsonImporter
{
    /**
     * Import valid records without replacing rows already managed by the database.
     *
     * @return array{imported: int, skipped: int}
     */
    public function import(string $path): array
    {
        if (! is_file($path)) {
            return ['imported' => 0, 'skipped' => 0];
        }

        try {
            $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return ['imported' => 0, 'skipped' => 1];
        }

        if (! is_array($decoded)) {
            return ['imported' => 0, 'skipped' => 1];
        }

        $imported = 0;
        $skipped = 0;

        foreach ($decoded as $key => $state) {
            if (! is_array($state)) {
                $skipped++;
                continue;
            }

            $id = $state['id'] ?? $key;
            $class = $state['class'] ?? null;
            $status = $state['status'] ?? null;

            if (! is_string($id) || $id === '' || ! is_string($class) || $class === '' || ! is_string($status) || ExtensionStatus::tryFrom($status) === null || ! class_exists($class)) {
                $skipped++;
                continue;
            }

            $extension = new $class();

            if (! $extension instanceof ExtensionInterface || $extension->manifest()->id !== $id) {
                $skipped++;
                continue;
            }

            // Existing DB values take precedence, making automatic and manual imports idempotent.
            $inserted = DB::table('extension_states')->insertOrIgnore([
                'extension_id' => $id,
                'extension_class' => $class,
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $inserted > 0 ? $imported++ : $skipped++;
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }
}
