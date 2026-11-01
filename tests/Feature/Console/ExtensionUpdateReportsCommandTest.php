<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $this->reportsDirectory = storage_path('app/extension-update-reports');
    File::ensureDirectoryExists($this->reportsDirectory);
    foreach (glob($this->reportsDirectory . '/u9-test-*.json') ?: [] as $file) {
        @unlink($file);
    }
});

afterEach(function (): void {
    foreach (glob($this->reportsDirectory . '/u9-test-*.json') ?: [] as $file) {
        @unlink($file);
    }
});

it('filters reports by failed outcome', function (): void {
    file_put_contents($this->reportsDirectory . '/u9-test-failed.json', json_encode([
        'started_at' => '2026-11-01T10:00:00+00:00', 'mode' => 'apply',
        'summary' => ['processed' => 1, 'failed' => 1, 'exit_code' => 1],
    ], JSON_THROW_ON_ERROR));
    file_put_contents($this->reportsDirectory . '/u9-test-success.json', json_encode([
        'started_at' => '2026-11-01T11:00:00+00:00', 'mode' => 'apply',
        'summary' => ['processed' => 1, 'failed' => 0, 'exit_code' => 0],
    ], JSON_THROW_ON_ERROR));

    $this->artisan('pixely:update:reports --status=failed')
        ->expectsOutputToContain('u9-test-failed.json')
        ->doesntExpectOutputToContain('u9-test-success.json')
        ->assertExitCode(0);
});

it('rejects an unsupported report status', function (): void {
    $this->artisan('pixely:update:reports --status=unknown')->assertExitCode(2);
});

it('exports report summaries to CSV', function (): void {
    file_put_contents($this->reportsDirectory . '/u9-test-export.json', json_encode([
        'started_at' => '2026-11-01T10:00:00+00:00', 'finished_at' => '2026-11-01T10:01:00+00:00',
        'mode' => 'apply', 'summary' => ['processed' => 2, 'failed' => 0, 'exit_code' => 0],
    ], JSON_THROW_ON_ERROR));
    $csv = storage_path('framework/testing/u9-report-export-' . bin2hex(random_bytes(4)) . '.csv');

    try {
        $this->artisan('pixely:update:reports', ['--export-csv' => $csv])
            ->expectsOutputToContain('Exported')
            ->assertExitCode(0);
        expect(file_get_contents($csv))->toContain('u9-test-export.json')->toContain('processed');
    } finally {
        @unlink($csv);
    }
});
