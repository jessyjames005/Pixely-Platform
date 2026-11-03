<?php

declare(strict_types=1);

namespace App\Core\Extensions\Installer;

interface ExtensionInstallerInterface
{
    /**
     * @return array{id: string, name: string, version: string}
     */
    public function install(string $zipPath, ?int $userId): array;

    /**
     * @return array{id: string, name: string, version: string}
     */
    public function update(string $zipPath, string $expectedId, ?int $userId): array;

    public function uninstall(string $id, ?int $userId): void;
}
