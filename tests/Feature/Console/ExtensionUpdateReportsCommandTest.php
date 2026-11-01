<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $this->reportsDirectory = storage_path('app/extension-update-reports');
    File::ensureDirectoryExists($this->reportsDirectory);
    foreach (glob($this->reportsDirectory . '/u8-test-*.json') ?: [] as $file) {
        @unlink($file);
    }
});

afterEach(function (): void {
    foreach (glob($this->reportsDirectory . '/u8-test-*.json') ?: [] as $file) {
        @unlink($file);
    }
});

it('lists JSON update reports', function (): void {
    file_put_contents($this->reportsDirectory . '/u8-test-list.json', json_encode([
        'started_at' => '2026-11-01T10:00:00+00:00',
        'mode' => 'dry-run',
        'summary' => ['processed' => 2, 'failed' => 0],
    ], JSON_THROW_ON_ERROR));

    $this->artisan('pixely:update:reports')
        ->expectsOutputToContain('u8-test-list.json')
        ->assertExitCode(0);
});

it('shows a report by filename and rejects path traversal', function (): void {
    file_put_contents($this->reportsDirectory . '/u8-test-show.json', '{"mode":"dry-run"}');

    $this->artisan('pixely:update:reports --show=u8-test-show.json')
        ->expectsOutputToContain('"mode": "dry-run"')
        ->assertExitCode(0);

    $this->artisan('pixely:update:reports --show=../.env')
        ->assertExitCode(2);
});

it('does not prune expired reports without explicit force', function (): void {
    $path = $this->reportsDirectory . '/u8-test-old.json';
    file_put_contents($path, '{"mode":"apply"}');
    touch($path, now()->subDays(10)->getTimestamp());

    $this->artisan('pixely:update:reports --prune=7')
        ->expectsOutputToContain('Nothing was deleted')
        ->assertExitCode(0);

    expect(is_file($path))->toBeTrue();
});

it('prunes expired reports only when force is supplied', function (): void {
    $path = $this->reportsDirectory . '/u8-test-expired.json';
    file_put_contents($path, '{"mode":"apply"}');
    touch($path, now()->subDays(10)->getTimestamp());

    $this->artisan('pixely:update:reports --prune=7 --force')
        ->expectsOutputToContain('Deleted 1 expired report(s).')
        ->assertExitCode(0);

    expect(is_file($path))->toBeFalse();
});
