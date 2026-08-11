<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Core\Extensions\Contracts\ExtensionStateRepositoryInterface;
use App\Core\Extensions\Enum\ExtensionStatus;
use App\Core\Extensions\Manager\ExtensionManager;
use Tests\Fakes\Extensions\MediaExtension;
use Tests\TestCase;

final class ExtensionCommandsPersistenceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $manager = app(ExtensionManager::class);

        /*
         * Gallery is already registered by the application bootstrap.
         *
         * Its dependency, Media, must also be available before enabling it.
         */
        if (! $manager->has('media')) {
            $manager->register(
                new MediaExtension(),
            );
        }
    }

    public function test_disable_command_persists_disabled_state(): void
    {
        $this->artisan('pixely:disable', [
            'extension' => 'gallery',
        ])
            ->assertSuccessful();

        $repository = app(
            ExtensionStateRepositoryInterface::class,
        );

        $state = $repository->find('gallery');

        $this->assertNotNull($state);

        $this->assertSame(
            ExtensionStatus::Disabled,
            $state->status,
        );
    }

    public function test_enable_command_persists_enabled_state(): void
    {
        $this->artisan('pixely:disable', [
            'extension' => 'gallery',
        ])
            ->assertSuccessful();

        $this->artisan('pixely:enable', [
            'extension' => 'gallery',
        ])
            ->assertSuccessful();

        $repository = app(
            ExtensionStateRepositoryInterface::class,
        );

        $state = $repository->find('gallery');

        $this->assertNotNull($state);

        $this->assertSame(
            ExtensionStatus::Enabled,
            $state->status,
        );
    }
}
