<?php

return [
    //App Configuration
    'name' => env('SP_APP_NAME', 'Streaming Plus'),
    'env' => env('SP_APP_ENV', 'local'),
    'debug' => (bool) env('SP_APP_DEBUG', true),
    'url' => env('SP_APP_URL', 'http://localhost:8095'),
    'timezone' => env('TIMEZONE', 'Europe/Rome'),
    'locale' => env('SP_APP_LOCALE', 'en'),
    'fallback_locale' => env('SP_APP_FALLBACK_LOCALE', 'en'),
    'key' => env('SP_APP_KEY'),
    'cipher' => 'AES-256-CBC',

    //Streaming Plus Configuration
    'imdb' => [
        'url' => env('IMDB_URL', 'https://www.imdb.com'),
        'api_url' => env('IMDB_API_URL', 'https://www.imdb.com/_next/data/{api_key}/en-US/title'),
        'suggestions_url' => env('IMDB_SUGGESTIONS_URL', 'https://v3.sg.media-imdb.com/suggestion/x/{search_term}.json'),
    ]
];
