<?php

declare(strict_types=1);

return [
    'object' => [
        'user' => [
            'name' => 'Name',
            'name_hint' => "The user's full display name.",
            'email' => 'Email',
            'email_hint' => 'Used to sign in; cannot be changed here.',
            'timezone' => 'Timezone',
            'timezone_hint' => 'Used to display dates and times throughout the admin.',
            'bio' => 'Bio',
            'bio_hint' => 'A short description shown on the profile.',
        ],
        'role' => [
            'name' => 'Role name',
            'name_hint' => 'A short, unique name identifying this role.',
            'users_count' => 'Users',
        ],
        'permission' => [
            'name' => 'Permission',
        ],
    ],
];
