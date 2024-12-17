<?php

/**
 * App Configuration
 */

return [
    'name' => 'Streaming Plus',
    'env' => 'local',
    'debug' => (bool) env('SP_APP_DEBUG', true),
    'url' => 'http://localhost:8095',
    'timezone' => env('TZ', 'Europe/Rome'),
    'locale' =>'en',
    'fallback_locale' => 'en',
    'key' => null,
    'cipher' => 'AES-256-CBC',
];
