<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Core\Extensions\Manager\ExtensionManager;
use Tests\Fakes\Extensions\MediaExtension;
use Tests\TestCase;

final class ExtensionCommandsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $manager = app(ExtensionManager::class);

        /*
         * Gallery depends on Media.
         *
         * Gallery is provided by the application bootstrap,
         * while Media must be available for dependency validation.
         */
        if (! $manager->has('media')) {
            $manager->register(
                new MediaExtension(),
            );
        }
    }

    public function test_it_can_disable_an_extension(): void
    {
        $manager = app(ExtensionManager::class);

        $this->artisan('pixely:disable', [
            'extension' => 'gallery',
        ])
            ->assertSuccessful();

        $this->assertFalse(
            $manager->isEnabled('gallery'),
        );
    }

    public function test_it_can_enable_an_extension(): void
    {
        $manager = app(ExtensionManager::class);

        $this->artisan('pixely:disable', [
            'extension' => 'gallery',
        ])
            ->assertSuccessful();

        $this->artisan('pixely:enable', [
            'extension' => 'gallery',
        ])
            ->assertSuccessful();

        $this->assertTrue(
            $manager->isEnabled('gallery'),
        );
    }
}
