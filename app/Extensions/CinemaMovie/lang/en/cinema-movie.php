<?php

declare(strict_types=1);

return [
    'state' => [
        'loading' => 'Loading movies…',
        'empty' => 'No movies yet.',
        'loading_genres' => 'Loading genres…',
        'empty_genres' => 'No genres yet.',
    ],
    'action' => [
        'add_movie' => 'Add movie',
        'edit_movie' => 'Edit movie',
        'delete_movie' => 'Delete movie',
        'add_genre' => 'Add genre',
        'edit_genre' => 'Edit genre',
        'delete_genre' => 'Delete genre',
    ],
    'title' => [
        'admin' => 'Cinema movies',
        'movie_catalogue' => 'Movie catalogue',
        'add_movie' => 'Add movie',
        'edit_movie' => 'Edit movie',
        'delete_movie' => 'Delete movie',
        'movie_details' => 'Movie details',
        'translations' => 'Titles and descriptions',
        'genres' => 'Genres',
        'add_genre' => 'Add genre',
        'edit_genre' => 'Edit genre',
        'delete_genre' => 'Delete genre',
    ],
    'object' => [
        'movie' => [
            'title' => 'Title',
            'original_title' => 'Original title',
            'english_title' => 'English title',
            'english_overview' => 'English overview',
            'french_title' => 'French title',
            'french_overview' => 'French overview',
            'genres' => 'Genres',
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
        'genre' => [
            'name' => 'Genre',
            'slug' => 'Slug',
            'english_name' => 'English name',
            'french_name' => 'French name',
        ],
    ],
    'msg' => [
        'movie_saved' => 'Movie saved.',
        'movie_deleted' => 'Movie deleted.',
        'confirm_delete_movie' => 'Delete “:title”? This cannot be undone.',
        'default_locale_required' => 'An English title is required before publication.',
        'genre_saved' => 'Genre saved.',
        'genre_deleted' => 'Genre deleted.',
        'confirm_delete_genre' => 'Delete “:name”? Movies will lose this genre.',
    ],
];
