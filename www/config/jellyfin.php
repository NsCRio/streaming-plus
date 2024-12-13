<?php

return [
    'url' => 'http://127.0.0.1:8097',
    'external_url' => 'http://127.0.0.1:8096',
    'movies_path' => sp_data_path('library/movies'),
    'series_path' => sp_data_path('library/tvSeries'),
    'virtualFolders' => [
        'Movies' => [
            'name' => 'Movies',
            'path' => sp_data_path('library/movies'),
            'type' => 'movies',
        ],
        'TV Series' => [
            'name' => 'TV Series',
            'path' => sp_data_path('library/tvSeries'),
            'type' => 'tvshows',
        ]
    ]
];
