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
];
