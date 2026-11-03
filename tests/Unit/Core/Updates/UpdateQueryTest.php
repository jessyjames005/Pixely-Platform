<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Updates;

use App\Core\Updates\DTO\UpdateQuery;
use App\Core\Updates\Enums\UpdateChannel;
use App\Core\Updates\Enums\UpdateTargetType;
use PHPUnit\Framework\TestCase;

final class UpdateQueryTest extends TestCase
{
    public function test_it_defaults_to_the_stable_channel(): void
    {
        $query = new UpdateQuery(
            type: UpdateTargetType::Extension,
            packageId: 'pixely/gallery',
            installedVersion: '1.0.0',
            runtimeVersions: ['php' => '8.3.0', 'pixely' => '1.0.0'],
        );

        $this->assertSame(UpdateChannel::Stable, $query->channel);
        $this->assertSame('8.3.0', $query->runtimeVersions['php']);
    }

    public function test_it_rejects_an_empty_package_identifier(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new UpdateQuery(
            type: UpdateTargetType::Extension,
            packageId: ' ',
            installedVersion: '1.0.0',
        );
    }
}
