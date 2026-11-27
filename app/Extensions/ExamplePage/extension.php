<?php

/**
 * ExamplePage extension manifest.
 *
 * Minimal reference extension demonstrating how an extension publishes
 * content through the Website Engine PageModel contract.
 */

declare(strict_types=1);

return [
    'id' => 'example-page',
    'name' => 'Example Page',
    'version' => '1.0.0',
    'minimum_kernel_version' => '1.0.0',
    'surfaces' => ['public', 'api'],
    'class' => App\Extensions\ExamplePage\ExamplePageExtension::class,
];
