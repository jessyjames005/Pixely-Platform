<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Extensions;

use App\Core\Extensions\Contracts\ExtensionStateRepositoryInterface;
use App\Core\Extensions\Enum\ExtensionStatus;
use App\Core\Extensions\Repositories\JsonExtensionStateRepository;
use App\Core\Extensions\State\ExtensionState;
use App\Extensions\Gallery\GalleryExtension;
use Tests\TestCase;

final class ExtensionStatePersistenceTest extends TestCase
{
    private string $storagePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storagePath = storage_path(
            'pixely/test-extension-states.json'
        );

        if (is_file($this->storagePath)) {
            unlink($this->storagePath);
        }
    }

    protected function tearDown(): void
    {
        if (is_file($this->storagePath)) {
            unlink($this->storagePath);
        }

        parent::tearDown();
    }

    public function test_it_persists_an_extension_state_to_json(): void
    {
        $repository = new JsonExtensionStateRepository(
            $this->storagePath,
        );

        $extension = new GalleryExtension();

        $repository->save(
            new ExtensionState(
                $extension,
                ExtensionStatus::Disabled,
            ),
        );

        $this->assertFileExists(
            $this->storagePath,
        );

        $content = file_get_contents(
            $this->storagePath,
        );

        $this->assertNotFalse($content);

        $data = json_decode(
            $content,
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame(
            'disabled',
            $data['gallery']['status'],
        );
    }

    public function test_it_can_restore_an_extension_state_from_json(): void
    {
        $repository = new JsonExtensionStateRepository(
            $this->storagePath,
        );

        $extension = new GalleryExtension();

        $repository->save(
            new ExtensionState(
                $extension,
                ExtensionStatus::Disabled,
            ),
        );

        unset($repository);

        $newRepository = new JsonExtensionStateRepository(
            $this->storagePath,
        );

        $state = $newRepository->find(
            'gallery',
        );

        $this->assertNotNull($state);

        $this->assertSame(
            'gallery',
            $state->extension->manifest()->id,
        );

        $this->assertSame(
            ExtensionStatus::Disabled,
            $state->status,
        );
    }
}
