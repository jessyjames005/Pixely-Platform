<?php

declare(strict_types=1);

return [
    'object' => [
        'user' => [
            'name' => [
                'label' => 'Name',
                'hint' => "The user's full display name.",
            ],
            'email' => [
                'label' => 'Email',
                'hint' => 'Used to sign in; cannot be changed here.',
            ],
            'timezone' => [
                'label' => 'Timezone',
                'hint' => 'Used to display dates and times throughout the admin.',
            ],
            'bio' => [
                'label' => 'Bio',
                'hint' => 'A short description shown on the profile.',
            ],
            'password' => [
                'label' => 'Password',
            ],
        ],
        'role' => [
            'name' => [
                'label' => 'Role name',
                'hint' => 'A short, unique name identifying this role.',
            ],
            'users_count' => [
                'label' => 'Users',
            ],
        ],
        'permission' => [
            'name' => [
                'label' => 'Permission',
            ],
        ],
    ],
];
