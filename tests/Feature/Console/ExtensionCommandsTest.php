<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Core\Extensions\Manager\ExtensionManager;
use App\Core\Extensions\State\ExtensionState;
use App\Core\Extensions\Contracts\ExtensionStateRepositoryInterface;
use App\Extensions\Gallery\GalleryExtension;
use Tests\TestCase;

final class ExtensionCommandsTest extends TestCase
{
    public function test_it_can_disable_an_extension(): void
    {
        $manager = app(ExtensionManager::class);

        $this->artisan('pixely:disable', [
            'extension' => 'gallery',
        ])
            ->assertSuccessful();

        $this->assertFalse(
            $manager->isEnabled('gallery')
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
            $manager->isEnabled('gallery')
        );
    }
}
