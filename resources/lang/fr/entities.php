<?php

declare(strict_types=1);

return [
    'object' => [
        'user' => [
            'name' => [
                'label' => 'Nom',
                'hint' => "Le nom complet affiché de l'utilisateur.",
            ],
            'email' => [
                'label' => 'E-mail',
                'hint' => 'Utilisé pour se connecter ; ne peut pas être modifié ici.',
            ],
            'timezone' => [
                'label' => 'Fuseau horaire',
                'hint' => "Utilisé pour afficher les dates et heures dans l'administration.",
            ],
            'bio' => [
                'label' => 'Biographie',
                'hint' => 'Une courte description affichée sur le profil.',
            ],
            'password' => [
                'label' => 'Mot de passe',
            ],
        ],
        'role' => [
            'name' => [
                'label' => 'Nom du rôle',
                'hint' => 'Un nom court et unique identifiant ce rôle.',
            ],
            'users_count' => [
                'label' => 'Utilisateurs',
            ],
        ],
        'permission' => [
            'name' => [
                'label' => 'Permission',
            ],
        ],
    ],
];
