<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// Start transaction for safety
DB::beginTransaction();

try {
    $adminRole = Role::where('name', 'admin')->first();
    if (! $adminRole) {
        echo "ERREUR: Rôle 'admin' introuvable en base de données.\n";
        DB::rollBack();
        exit(1);
    }

    $allPermissions = Permission::all();
    if ($allPermissions->isEmpty()) {
        echo "ATTENTION: Aucune permission trouvée en base de données. Veuillez d'abord lancer le seeder.\n";
        DB::rollBack();
        exit(1);
    }

    // Sync all permissions to admin role
    $adminRole->syncPermissions($allPermissions);

    DB::commit();

    echo "SUCCÈS: Rôle 'admin' synchronisé avec {$allPermissions->count()} permissions.\n";
    echo "Permissions attribuées:\n";
    foreach ($adminRole->permissions as $perm) {
        echo " - {$perm->name}\n";
    }
} catch (Throwable $e) {
    DB::rollBack();
    echo "ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}