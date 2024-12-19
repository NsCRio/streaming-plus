<?php

return [
    'url' => 'http://localhost:8097',
    'external_url' => 'http://localhost:8096',
    'api_key_path' => sp_data_path('app/api.key'),
    'movies_path' => sp_data_path('library/movies'),
    'series_path' => sp_data_path('library/tvSeries'),
    'virtual_folders' => [
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
    ],
    'delete_unused_items_after' => 5, //days
    'update_series_after' => 12, //hours
    'delete_streams_after' => 3, //hours
    'supported_extensions' => [
        'mp4',
        'mkv',
        'webm',
        'ts',
        'm2t',
        'm3u',
        'm3u8'
    ]
];
