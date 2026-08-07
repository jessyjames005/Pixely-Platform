<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Extensions\Enum;

use App\Core\Extensions\Enum\ExtensionStatus;
use PHPUnit\Framework\TestCase;

/**
 * Tests the extension status enum.
 */
final class ExtensionStatusTest extends TestCase
{
    /**
     * Ensure enabled status value is correct.
     */
    public function test_enabled_status_value(): void
    {
        $this->assertSame(
            'enabled',
            ExtensionStatus::Enabled->value,
        );
    }

    /**
     * Ensure disabled status value is correct.
     */
    public function test_disabled_status_value(): void
    {
        $this->assertSame(
            'disabled',
            ExtensionStatus::Disabled->value,
        );
    }
}
