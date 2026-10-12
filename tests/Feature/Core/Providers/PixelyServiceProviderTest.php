<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Providers;

use App\Core\Extensions\Contracts\ExtensionStateRepositoryInterface;
use App\Core\Extensions\Repositories\InMemoryExtensionStateRepository;
use Tests\TestCase;

final class PixelyServiceProviderTest extends TestCase
{
    /**
     * PixelyServiceProvider isolates extension state in memory during tests
     * (the JSON repository writes to storage/). Production binds the JSON one.
     */
    public function test_it_binds_an_in_memory_extension_state_repository_while_testing(): void
    {
        $repository = app(
            ExtensionStateRepositoryInterface::class
        );

        $this->assertInstanceOf(
            InMemoryExtensionStateRepository::class,
            $repository,
        );
    }
}
