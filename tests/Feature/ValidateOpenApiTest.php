<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

test('it validates a valid openapi specification', function () {
    $exitCode = Artisan::call('openapi:validate');

    expect($exitCode)->toBe(0);
});

test('it rejects a missing openapi specification', function () {
    $path = base_path('docs/api/openapi.yml');
    $backupPath = $path . '.test-backup';

    rename($path, $backupPath);

    try {
        $exitCode = Artisan::call('openapi:validate');

        expect($exitCode)->toBe(1);
    } finally {
        rename($backupPath, $path);
    }
});

test('it rejects an invalid openapi specification', function () {
    $path = base_path('docs/api/openapi.yml');
    $backupPath = $path . '.test-backup';

    copy($path, $backupPath);

    try {
        file_put_contents(
            $path,
            <<<'YAML'
openapi: 3.0.3
info:
  title: Invalid API
paths:
YAML
        );

        $exitCode = Artisan::call('openapi:validate');

        expect($exitCode)->toBe(1);
    } finally {
        copy($backupPath, $path);
        unlink($backupPath);
    }
});
