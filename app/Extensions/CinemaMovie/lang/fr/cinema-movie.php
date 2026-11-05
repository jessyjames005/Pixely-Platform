<?php

declare(strict_types=1);

return [
    'state' => [
        'loading' => 'Chargement des films…',
        'empty' => 'Aucun film pour le moment.',
    ],
    'action' => [
        'add_movie' => 'Ajouter un film',
        'edit_movie' => 'Modifier le film',
        'delete_movie' => 'Supprimer le film',
    ],
    'title' => [
        'admin' => 'Films',
        'movie_catalogue' => 'Catalogue des films',
        'add_movie' => 'Ajouter un film',
        'edit_movie' => 'Modifier le film',
        'delete_movie' => 'Supprimer le film',
        'movie_details' => 'Détails du film',
        'translations' => 'Titres et descriptions',
    ],
    'object' => [
        'movie' => [
            'title' => 'Titre',
            'original_title' => 'Titre original',
            'english_title' => 'Titre anglais',
            'english_overview' => 'Résumé en anglais',
            'french_title' => 'Titre français',
            'french_overview' => 'Résumé en français',
            'release_date' => 'Date de sortie',
            'runtime_minutes' => 'Durée (minutes)',
            'age_rating' => 'Classification par âge',
            'production_status' => [
                'label' => 'État de production',
                'rumored' => 'À confirmer',
                'planned' => 'Prévu',
                'in_production' => 'En production',
                'post_production' => 'Post-production',
                'released' => 'Sorti',
                'canceled' => 'Annulé',
            ],
            'publication_status' => [
                'label' => 'État de publication',
                'draft' => 'Brouillon',
                'published' => 'Publié',
                'archived' => 'Archivé',
            ],
        ],
    ],
    'msg' => [
        'movie_saved' => 'Film enregistré.',
        'movie_deleted' => 'Film supprimé.',
        'confirm_delete_movie' => 'Supprimer « :title » ? Cette action est irréversible.',
        'default_locale_required' => 'Un titre anglais est requis avant la publication.',
    ],
];
