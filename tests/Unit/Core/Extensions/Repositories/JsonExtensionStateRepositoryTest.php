<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Extensions\Repositories;

use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Enum\ExtensionStatus;
use App\Core\Extensions\Repositories\JsonExtensionStateRepository;
use App\Core\Extensions\State\ExtensionState;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\Extensions\FakeExtension;

final class JsonExtensionStateRepositoryTest extends TestCase
{
    private string $storagePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storagePath = sys_get_temp_dir()
            .'/pixely-extension-states-'
            .uniqid('', true)
            .'.json';
    }

    protected function tearDown(): void
    {
        if (is_file($this->storagePath)) {
            unlink($this->storagePath);
        }

        parent::tearDown();
    }

    public function test_it_returns_empty_array_when_storage_file_does_not_exist(): void
    {
        $repository = new JsonExtensionStateRepository(
            $this->storagePath,
        );

        $this->assertSame([], $repository->all());
    }

    public function test_it_can_save_and_find_an_extension_state(): void
    {
        $repository = new JsonExtensionStateRepository(
            $this->storagePath,
        );

        $extension = new FakeExtension();

        $state = new ExtensionState(
            $extension,
            ExtensionStatus::Enabled,
        );

        $repository->save($state);

        $result = $repository->find('gallery');

        $this->assertNotNull($result);

        $this->assertSame(
            'gallery',
            $result->extension->manifest()->id,
        );

        $this->assertSame(
            ExtensionStatus::Enabled,
            $result->status,
        );
    }

    public function test_it_can_return_all_extension_states(): void
    {
        $repository = new JsonExtensionStateRepository(
            $this->storagePath,
        );

        $repository->save(
            new ExtensionState(
                new FakeExtension(),
                ExtensionStatus::Enabled,
            ),
        );

        $states = $repository->all();

        $this->assertCount(1, $states);

        $this->assertArrayHasKey(
            'gallery',
            $states,
        );
    }

    public function test_it_can_update_an_extension_state(): void
    {
        $repository = new JsonExtensionStateRepository(
            $this->storagePath,
        );

        $extension = new FakeExtension();

        $repository->save(
            new ExtensionState(
                $extension,
                ExtensionStatus::Enabled,
            ),
        );

        $repository->update(
            new ExtensionState(
                $extension,
                ExtensionStatus::Disabled,
            ),
        );

        $state = $repository->find('gallery');

        $this->assertNotNull($state);

        $this->assertSame(
            ExtensionStatus::Disabled,
            $state->status,
        );
    }

    public function test_it_persists_state_to_json_file(): void
    {
        $repository = new JsonExtensionStateRepository(
            $this->storagePath,
        );

        $repository->save(
            new ExtensionState(
                new FakeExtension(),
                ExtensionStatus::Enabled,
            ),
        );

        $this->assertFileExists($this->storagePath);

        $content = file_get_contents($this->storagePath);

        $this->assertNotFalse($content);

        $data = json_decode(
            $content,
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertArrayHasKey(
            'gallery',
            $data,
        );

        $this->assertSame(
            'enabled',
            $data['gallery']['status'],
        );
    }
}
