<?php

declare(strict_types=1);

return [
    'object' => [
        'user' => [
            'name' => 'Nom',
            'name.hint' => "Le nom complet affiché de l'utilisateur.",
            'email' => 'E-mail',
            'email.hint' => 'Utilisé pour se connecter ; ne peut pas être modifié ici.',
            'timezone' => 'Fuseau horaire',
            'timezone.hint' => "Utilisé pour afficher les dates et heures dans l'administration.",
            'bio' => 'Biographie',
            'bio.hint' => 'Une courte description affichée sur le profil.',
        ],
        'role' => [
            'name' => 'Nom du rôle',
            'name.hint' => 'Un nom court et unique identifiant ce rôle.',
            'users_count' => 'Utilisateurs',
        ],
        'permission' => [
            'name' => 'Permission',
        ],
    ],
];
