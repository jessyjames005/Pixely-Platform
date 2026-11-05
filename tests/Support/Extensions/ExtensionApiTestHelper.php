<?php

declare(strict_types=1);

namespace Tests\Support\Extensions;

use App\Core\Extensions\Permissions\ExtensionPermissionsInterface;
use App\Models\User;
use InvalidArgumentException;
use Spatie\Permission\Models\Permission;

final class ExtensionApiTestHelper
{
    /**
     * @return array{Accept: string, Content-Type: string}
     */
    public static function jsonApiHeaders(): array
    {
        return [
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
        ];
    }

    /**
     * Creates a user assigned to the named permission declared by the extension.
     */
    public static function createUserWithPermission(
        ExtensionPermissionsInterface $extension,
        string $declaredPermission,
    ): User {
        if (! in_array($declaredPermission, $extension->declaredPermissions(), true)) {
            throw new InvalidArgumentException(
                "Permission [{$declaredPermission}] is not declared by the extension."
            );
        }

        $permission = Permission::query()->firstOrCreate([
            'name' => $declaredPermission,
            'guard_name' => 'web',
        ]);
        $user = User::factory()->create();
        $user->givePermissionTo($permission);

        return $user;
    }
}
