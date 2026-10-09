<?php

declare(strict_types=1);

namespace App\Core\Extensions\Configuration;

/**
 * Optional contract for extensions providing settings schema per surface.
 *
 * An extension implements this only if it needs to provide different
 * settings schemas for different surfaces (public, user, admin).
 */
interface ExtensionSettingsInterface
{
    /**
     * Return the extension settings schema for the given surface.
     *
     * @param string $surface One of: public, user, admin, api
     * @return array<string, mixed> Settings schema (field definitions)
     */
    public function settings(string $surface): array;
}
