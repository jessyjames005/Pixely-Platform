<?php

declare(strict_types=1);

return [
    'object' => [
        'user' => [
            'name' => 'Name',
            'name.hint' => "The user's full display name.",
            'email' => 'Email',
            'email.hint' => 'Used to sign in; cannot be changed here.',
            'timezone' => 'Timezone',
            'timezone.hint' => 'Used to display dates and times throughout the admin.',
            'bio' => 'Bio',
            'bio.hint' => 'A short description shown on the profile.',
        ],
        'role' => [
            'name' => 'Role name',
            'name.hint' => 'A short, unique name identifying this role.',
            'users_count' => 'Users',
        ],
        'permission' => [
            'name' => 'Permission',
        ],
    ],
];
