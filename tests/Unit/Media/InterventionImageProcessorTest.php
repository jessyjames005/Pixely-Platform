<?php

declare(strict_types=1);

namespace Tests\Unit\Media;

use App\Media\Processors\InterventionImageProcessor;
use PHPUnit\Framework\TestCase;

/**
 * Tests InterventionImageProcessor.
 */
final class InterventionImageProcessorTest extends TestCase
{
    /**
     * Ensure the processor can be instantiated.
     */
    public function test_it_can_be_instantiated(): void
    {
        $processor = new InterventionImageProcessor();

        $this->assertInstanceOf(
            InterventionImageProcessor::class,
            $processor
        );
    }
}
