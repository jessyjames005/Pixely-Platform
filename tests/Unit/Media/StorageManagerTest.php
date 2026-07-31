<?php

declare(strict_types=1);

namespace Tests\Unit\Media;

use App\Media\Contracts\StorageInterface;
use App\Media\Services\StorageManager;
use Illuminate\Http\UploadedFile;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Tests StorageManager.
 */
final class StorageManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    /**
     * Ensure the manager delegates storage to the driver.
     */
    public function test_it_stores_a_file_using_the_driver(): void
    {
        $driver = Mockery::mock(StorageInterface::class);

        $file = Mockery::mock(UploadedFile::class);

        $driver
            ->shouldReceive('store')
            ->once()
            ->with($file)
            ->andReturn('photos/test.jpg');

        $manager = new StorageManager($driver);

        $this->assertSame(
            'photos/test.jpg',
            $manager->store($file)
        );
    }
}
