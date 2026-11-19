<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\Models\Role;

final class E2eUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('e2e.password');

        if (
            ! is_string($password)
            || trim($password) === ''
        ) {
            throw new RuntimeException('E2E_PASSWORD must be set before seeding browser test users.');
        }

        foreach (
            [
                'admin@pixely.test' => ['name' => 'E2E Admin', 'role' => 'admin'],
                'editor@pixely.test' => ['name' => 'E2E Editor', 'role' => 'editor'],
            ] as $email => $persona
        ) {
            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $persona['name'],
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                ],
            );

            $user->syncRoles(Role::findByName($persona['role'], 'web'));
        }
    }
}
