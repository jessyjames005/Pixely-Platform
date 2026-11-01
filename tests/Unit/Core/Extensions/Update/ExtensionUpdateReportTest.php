<?php

declare(strict_types=1);

use App\Core\Extensions\Update\ExtensionUpdateReport;

it('writes a readable JSON update report atomically', function (): void {
    $path = storage_path('framework/testing/update-report-' . bin2hex(random_bytes(4)) . '.json');
    $reporter = new ExtensionUpdateReport();

    try {
        expect($reporter->write($path, [
            'schema_version' => 1,
            'mode' => 'dry-run',
            'summary' => ['processed' => 1, 'failed' => 0],
            'items' => [['extension' => 'gallery', 'status' => 'planned']],
        ]))->toBe($path)
            ->and(json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR)['items'][0]['status'])->toBe('planned');
    } finally {
        @unlink($path);
    }
});

it('rejects invalid JSON payloads instead of writing a corrupt report', function (): void {
    $path = storage_path('framework/testing/update-report-' . bin2hex(random_bytes(4)) . '.json');

    try {
        (new ExtensionUpdateReport())->write($path, ['invalid' => NAN]);
    } finally {
        @unlink($path);
    }
})->throws(JsonException::class);
