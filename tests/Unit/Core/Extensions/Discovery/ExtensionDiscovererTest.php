<?php

declare(strict_types=1);

use App\Core\Extensions\Discovery\ExtensionDiscoverer;

it('discovers extension directories', function () {
    $discoverer = new ExtensionDiscoverer();

    $extensions = $discoverer->discover(
        dirname(__DIR__, 5) . '/app/Extensions'
    );

    expect($extensions)->toBeArray();
    expect($extensions)->toHaveCount(1);
});
