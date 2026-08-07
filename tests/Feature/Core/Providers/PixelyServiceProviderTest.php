<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Providers;

use App\Core\Extensions\Contracts\ExtensionStateRepositoryInterface;
use App\Core\Extensions\Repositories\JsonExtensionStateRepository;
use Tests\TestCase;

final class PixelyServiceProviderTest extends TestCase
{
    public function test_it_binds_the_extension_state_repository_to_json_repository(): void
    {
        $repository = app(
            ExtensionStateRepositoryInterface::class
        );

        $this->assertInstanceOf(
            JsonExtensionStateRepository::class,
            $repository,
        );
    }
}
