<?php

declare(strict_types=1);

namespace App\Core\Extensions\Permissions;

/**
 * Optional contract for extensions that declare their own permissions.
 *
 * An extension implements this only if it needs permission-gated
 * routes/actions. Follows the same optional-interface convention as
 * ExtensionConfigurableInterface.
 */
interface ExtensionPermissionsInterface
{
    /**
     * Return the full permission names this extension needs.
     *
     * Must follow the platform convention: <domain>.<object>.<action>
     * for CRUD objects (view/manage/delete), or an explicit action
     * name for non-CRUD tools.
     *
     * @return array<int, string>
     */
    public function declaredPermissions(): array;
}
