<?php

declare(strict_types=1);

use App\Core\Extensions\Installer\StaticExtensionManifestParser;

it('parses a literal extension manifest without evaluating it', function () {
    $directory = dirname(__DIR__, 5) . '/storage/framework/testing/static-manifest-' . bin2hex(random_bytes(6));
    mkdir($directory, 0700, true);
    $path = $directory . DIRECTORY_SEPARATOR . 'extension.php';
    $source = <<<'PHP'
<?php
/* A comment is safe metadata. */
declare(strict_types=1);

return [
    'id' => 'gallery',
    'name' => 'Gallery',
    'version' => '1.2.0',
    'class' => App\Extensions\Gallery\GalleryExtension::class,
    'requires' => ['files'],
    'require_versions' => ['files' => '1.0.0'],
    'enabled' => true,
    'metadata' => ['nullable' => null, 'count' => 3],
];
PHP;
    file_put_contents($path, $source);

    try {
        $manifest = (new StaticExtensionManifestParser())->parseFile($path);

        expect($manifest['id'])->toBe('gallery')
            ->and($manifest['class'])->toBe('App\\Extensions\\Gallery\\GalleryExtension')
            ->and($manifest['requires'])->toBe(['files'])
            ->and($manifest['enabled'])->toBeTrue()
            ->and($manifest['metadata'])->toBe(['nullable' => null, 'count' => 3]);

        file_put_contents($path, substr($source, strlen('<?php')));
        expect((new StaticExtensionManifestParser())->parseFile($path)['id'])->toBe('gallery');
    } finally {
        (new Illuminate\Filesystem\Filesystem())->deleteDirectory($directory);
    }
});

it('rejects executable expressions and statements without running them', function () {
    $directory = dirname(__DIR__, 5) . '/storage/framework/testing/static-manifest-' . bin2hex(random_bytes(6));
    mkdir($directory, 0700, true);
    $path = $directory . DIRECTORY_SEPARATOR . 'extension.php';
    $sideEffect = $directory . DIRECTORY_SEPARATOR . 'executed';
    file_put_contents($path, sprintf(
        "<?php return ['id' => file_put_contents(%s, 'executed')];",
        var_export($sideEffect, true),
    ));

    try {
        expect(fn () => (new StaticExtensionManifestParser())->parseFile($path))
            ->toThrow(RuntimeException::class, 'Unsupported');
        expect(file_exists($sideEffect))->toBeFalse();
    } finally {
        (new Illuminate\Filesystem\Filesystem())->deleteDirectory($directory);
    }
});

it('rejects trailing executable statements', function () {
    $directory = dirname(__DIR__, 5) . '/storage/framework/testing/static-manifest-' . bin2hex(random_bytes(6));
    mkdir($directory, 0700, true);
    $path = $directory . DIRECTORY_SEPARATOR . 'extension.php';
    file_put_contents($path, "<?php return ['id' => 'gallery']; file_put_contents('unsafe', 'x');");

    try {
        expect(fn () => (new StaticExtensionManifestParser())->parseFile($path))
            ->toThrow(RuntimeException::class);
    } finally {
        (new Illuminate\Filesystem\Filesystem())->deleteDirectory($directory);
    }
});
