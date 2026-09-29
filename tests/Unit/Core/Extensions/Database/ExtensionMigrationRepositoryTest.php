<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Extensions\Database;

use App\Core\Extensions\Database\ExtensionMigrationRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ExtensionMigrationRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_records_and_reads_a_migration(): void
    {
        $repository = new ExtensionMigrationRepository();

        $repository->record(
            'Gallery',
            '2026_08_10_000000_create_photos_table',
            1,
        );

        $this->assertSame(
            [
                '2026_08_10_000000_create_photos_table',
            ],
            $repository->getRan('Gallery'),
        );

        $this->assertSame(
            1,
            $repository->getBatch(
                'Gallery',
                '2026_08_10_000000_create_photos_table',
            ),
        );
    }

    public function test_migrations_are_isolated_by_extension(): void
    {
        $repository = new ExtensionMigrationRepository();

        $repository->record('Gallery', 'migration_one', 1);
        $repository->record('Files', 'migration_one', 1);

        $this->assertSame(
            ['migration_one'],
            $repository->getRan('Gallery'),
        );

        $this->assertSame(
            ['migration_one'],
            $repository->getRan('Files'),
        );
    }

    public function test_it_deletes_only_the_requested_extension_migration(): void
    {
        $repository = new ExtensionMigrationRepository();

        $repository->record('Gallery', 'migration_one', 1);
        $repository->record('Gallery', 'migration_two', 2);

        $repository->delete('Gallery', 'migration_two');

        $this->assertSame(
            ['migration_one'],
            $repository->getRan('Gallery'),
        );
    }

    public function test_it_returns_the_next_batch_number(): void
    {
        $repository = new ExtensionMigrationRepository();

        $this->assertSame(
            1,
            $repository->getNextBatchNumber('Gallery'),
        );

        $repository->record('Gallery', 'migration_one', 1);
        $repository->record('Gallery', 'migration_two', 3);

        $this->assertSame(
            4,
            $repository->getNextBatchNumber('Gallery'),
        );
    }
}
