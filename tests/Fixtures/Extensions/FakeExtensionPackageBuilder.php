<?php

declare(strict_types=1);

namespace Tests\Fixtures\Extensions;

use ZipArchive;

/**
 * Builds fake extension zip packages in a temp directory for testing
 * the installer, without committing binary zip fixtures to the repo.
 */
final class FakeExtensionPackageBuilder
{
    /**
     * Builds a valid, minimal but real extension package: a manifest
     * plus a class that actually implements ExtensionInterface.
     */
    public static function validPackage(string $id = 'demo', string $version = '1.0.0'): string
    {
        $sourceDir = sys_get_temp_dir() . '/pixely-fake-ext-' . uniqid('', true);
        mkdir($sourceDir, 0777, true);

        $className = 'DemoExtension' . substr(md5($id . $version . microtime()), 0, 8);

        file_put_contents($sourceDir . '/extension.php', <<<PHP
        <?php
        declare(strict_types=1);
        return [
            'id' => '{$id}',
            'name' => 'Demo Extension',
            'version' => '{$version}',
            'class' => App\Extensions\Demo\\{$className}::class,
        ];
        PHP);

        mkdir($sourceDir . '/src');
        file_put_contents($sourceDir . '/src/' . $className . '.php', <<<PHP
        <?php
        declare(strict_types=1);
        namespace App\Extensions\Demo;
        use App\Core\Extensions\Contracts\ExtensionInterface;
        use App\Core\Extensions\Manifest\ExtensionManifest;
        final class {$className} implements ExtensionInterface
        {
            public function manifest(): ExtensionManifest
            {
                return new ExtensionManifest(
                    id: '{$className}',
                    name: 'Demo Extension',
                    version: '1.0.0',
                    class: self::class,
                    path: 'app/Extensions/Demo',
                    dependencies: [],
                );
            }
            public function providers(): array { return []; }
            public function boot(): void {}
        }
        PHP);

        return self::zip($sourceDir);
    }

    /**
     * Builds a package with no extension.php at all.
     */
    public static function missingManifestPackage(): string
    {
        $sourceDir = sys_get_temp_dir() . '/pixely-fake-ext-' . uniqid('', true);
        mkdir($sourceDir, 0777, true);
        file_put_contents($sourceDir . '/readme.txt', 'not an extension');

        return self::zip($sourceDir);
    }

    /**
     * Builds a package whose manifest declares a class that doesn't
     * actually exist / doesn't implement ExtensionInterface.
     */
    public static function invalidClassPackage(string $id = 'broken'): string
    {
        $sourceDir = sys_get_temp_dir() . '/pixely-fake-ext-' . uniqid('', true);
        mkdir($sourceDir, 0777, true);

        file_put_contents($sourceDir . '/extension.php', <<<PHP
        <?php
        declare(strict_types=1);
        return [
            'id' => '{$id}',
            'name' => 'Broken Extension',
            'version' => '1.0.0',
            'class' => 'App\\\\Extensions\\\\DoesNotExist\\\\NoSuchClass',
        ];
        PHP);

        return self::zip($sourceDir);
    }

    /**
     * Builds a zip containing a zip-slip path traversal entry.
     */
    public static function zipSlipPackage(): string
    {
        $zipPath = sys_get_temp_dir() . '/pixely-fake-ext-' . uniqid('', true) . '.zip';

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);
        $zip->addFromString('extension.php', "<?php\nreturn ['id' => 'evil', 'name' => 'Evil', 'version' => '1.0.0', 'class' => 'X'];");
        $zip->addFromString('../../../evil.php', "<?php\necho 'pwned';");
        $zip->close();

        return $zipPath;
    }

    /**
     * Builds a zip with an invalid (non-semver) version string.
     */
    public static function invalidVersionPackage(): string
    {
        $sourceDir = sys_get_temp_dir() . '/pixely-fake-ext-' . uniqid('', true);
        mkdir($sourceDir, 0777, true);

        file_put_contents($sourceDir . '/extension.php', <<<PHP
        <?php
        declare(strict_types=1);
        return [
            'id' => 'demo',
            'name' => 'Demo',
            'version' => 'not-a-version',
            'class' => 'App\\\\Extensions\\\\Demo\\\\X',
        ];
        PHP);

        return self::zip($sourceDir);
    }

    private static function zip(string $sourceDir): string
    {
        $zipPath = $sourceDir . '.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($files as $file) {
            $relativePath = substr($file->getPathname(), strlen($sourceDir) + 1);
            $zip->addFile($file->getPathname(), $relativePath);
        }

        $zip->close();

        return $zipPath;
    }
}
