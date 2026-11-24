<?php

declare(strict_types=1);

use App\Extensions\Music\MusicExtension;

it('declares the music extension manifest', function () {
    $manifest = (new MusicExtension())->manifest();

    expect($manifest->id)->toBe('music')
        ->and($manifest->name)->toBe('Music')
        ->and($manifest->minimum_kernel_version)->toBe('1.0.0');
});
