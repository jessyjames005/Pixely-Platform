<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Extensions\Repositories;

use App\Core\Extensions\Enum\ExtensionStatus;
use App\Core\Extensions\Repositories\InMemoryExtensionStateRepository;
use App\Core\Extensions\State\ExtensionState;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\Extensions\FakeExtension;

/**
 * Tests the in-memory extension state repository.
 */
final class InMemoryExtensionStateRepositoryTest extends TestCase
{
    /**
     * Ensure an extension state can be saved and retrieved.
     */
    public function test_it_can_save_and_find_a_state(): void
    {
        $repository = new InMemoryExtensionStateRepository();

        $state = new ExtensionState(
            new FakeExtension(),
            ExtensionStatus::Enabled,
        );

        $repository->save($state);

        $this->assertSame(
            $state,
            $repository->find('gallery'),
        );
    }

    /**
     * Ensure all saved states are returned.
     */
    public function test_it_returns_all_states(): void
    {
        $repository = new InMemoryExtensionStateRepository();

        $repository->save(
            new ExtensionState(
                new FakeExtension(),
                ExtensionStatus::Enabled,
            )
        );

        $this->assertCount(
            1,
            $repository->all(),
        );
    }
}
