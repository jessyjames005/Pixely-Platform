<?php

declare(strict_types=1);

return [
    'state' => [
        'loading' => 'Loading movies…',
        'empty' => 'No movies yet.',
    ],
    'action' => [
        'add_movie' => 'Add movie',
        'edit_movie' => 'Edit movie',
        'delete_movie' => 'Delete movie',
    ],
    'title' => [
        'admin' => 'Cinema movies',
        'movie_catalogue' => 'Movie catalogue',
        'add_movie' => 'Add movie',
        'edit_movie' => 'Edit movie',
        'delete_movie' => 'Delete movie',
        'movie_details' => 'Movie details',
        'translations' => 'Titles and descriptions',
    ],
    'object' => [
        'movie' => [
            'title' => 'Title',
            'original_title' => 'Original title',
            'english_title' => 'English title',
            'english_overview' => 'English overview',
            'french_title' => 'French title',
            'french_overview' => 'French overview',
            'release_date' => 'Release date',
            'runtime_minutes' => 'Runtime (minutes)',
            'age_rating' => 'Age rating',
            'production_status' => [
                'label' => 'Production status',
                'rumored' => 'Rumored',
                'planned' => 'Planned',
                'in_production' => 'In production',
                'post_production' => 'Post production',
                'released' => 'Released',
                'canceled' => 'Canceled',
            ],
            'publication_status' => [
                'label' => 'Publication status',
                'draft' => 'Draft',
                'published' => 'Published',
                'archived' => 'Archived',
            ],
        ],
    ],
    'msg' => [
        'movie_saved' => 'Movie saved.',
        'movie_deleted' => 'Movie deleted.',
        'confirm_delete_movie' => 'Delete “:title”? This cannot be undone.',
        'default_locale_required' => 'An English title is required before publication.',
    ],
];
