<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Extensions\State;

use App\Core\Extensions\Enum\ExtensionStatus;
use App\Core\Extensions\State\ExtensionState;
use Tests\Fakes\Extensions\FakeExtension;
use PHPUnit\Framework\TestCase;

/**
 * Tests the extension state.
 */
final class ExtensionStateTest extends TestCase
{
    /**
     * Ensure the extension state stores its values.
     */
    public function test_it_stores_extension_and_status(): void
    {
        $extension = new FakeExtension();

        $state = new ExtensionState(
            $extension,
            ExtensionStatus::Enabled,
        );

        $this->assertSame(
            $extension,
            $state->extension,
        );

        $this->assertSame(
            ExtensionStatus::Enabled,
            $state->status,
        );
    }
}
