<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

it('scaffolds a complete extension and prints its Docker lifecycle commands', function () {
    $originalBasePath = base_path();
    $temporaryBasePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pixely-make-extension-' . uniqid('', true);
    $extensionPath = $temporaryBasePath . '/app/Extensions/CinemaMovie';

    File::ensureDirectoryExists($temporaryBasePath);
    app()->setBasePath($temporaryBasePath);

    try {
        $this->artisan('make:extension', ['name' => 'CinemaMovie'])
            ->expectsOutputToContain('docker compose exec app php artisan pixely:extension:migrate cinema-movie')
            ->expectsOutputToContain('docker compose exec app php artisan pixely:extension:migration-status cinema-movie')
            ->expectsOutputToContain('docker compose exec app php artisan pixely:extensions')
            ->assertExitCode(0);

        expect(File::exists($extensionPath . '/extension.php'))->toBeTrue()
            ->and(File::get($extensionPath . '/extension.php'))->toContain("'minimum_kernel_version' => '1.0.0'")
            ->and(File::exists($extensionPath . '/Providers/CinemaMovieServiceProvider.php'))->toBeTrue()
            ->and(File::get($extensionPath . '/Providers/CinemaMovieServiceProvider.php'))->not->toContain('loadMigrationsFrom')
            ->and(File::get($extensionPath . '/routes/api.php'))->toContain("permission:cinema-movie.items.view")
            ->and(File::exists($extensionPath . '/resources/js/models/CinemaMovie.ts'))->toBeTrue()
            ->and(File::exists($extensionPath . '/resources/js/store/cinema-movie.store.ts'))->toBeTrue()
            ->and(File::get($extensionPath . '/resources/js/views/CinemaMovieView.vue'))->toContain("../store/cinema-movie.store")
            ->and(File::exists($extensionPath . '/resources/js/views/CinemaMovieView.vue'))->toBeTrue()
            ->and(File::exists($extensionPath . '/resources/js/nav.ts'))->toBeTrue()
            ->and(File::get($extensionPath . '/tests/Unit/CinemaMovieExtensionTest.php'))->toContain("declares the cinema-movie extension manifest", "->toBe('cinema-movie')")
            ->and(File::get($extensionPath . '/tests/Functional/CinemaMovieApiTest.php'))->toContain('use Tests\\TestCase;', 'uses(TestCase::class);', "requires authentication to list CinemaMovie items", "getJson('/api/v1/cinema-movie')")
            ->and(File::get($extensionPath . '/tests/E2E/CinemaMovie.spec.ts'))->toContain("from '@playwright/test'", "process.env.E2E_USER_EMAIL", "process.env.E2E_USER_PASSWORD", "page.goto('/login')", "page.goto('/admin/cinema-movie')", "name: 'CinemaMovie'");
    } finally {
        app()->setBasePath($originalBasePath);
        File::deleteDirectory($temporaryBasePath);
    }
});
