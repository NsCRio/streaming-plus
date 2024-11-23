<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('SP_DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('SP_DB_DATABASE', database_path('database.sqlite')),
            'prefix' => env('SP_DB_PREFIX', ''),
        ],

        'mysql' => [
            'driver' => 'mysql',
            'host' => env('SP_DB_HOST', '127.0.0.1'),
            'port' => env('SP_DB_PORT', 3306),
            'database' => env('SP_DB_DATABASE', 'forge'),
            'username' => env('SP_DB_USERNAME', 'forge'),
            'password' => env('SP_DB_PASSWORD', ''),
            'unix_socket' => env('SP_DB_SOCKET', ''),
            'charset' => env('SP_DB_CHARSET', 'utf8mb4'),
            'collation' => env('SP_DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => env('SP_DB_PREFIX', ''),
            'strict' => env('SP_DB_STRICT_MODE', false),
            'engine' => env('SP_DB_ENGINE'),
            'timezone' => env('SP_DB_TIMEZONE', '+00:00'),
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('SP_DB_HOST', '127.0.0.1'),
            'port' => env('SP_DB_PORT', 5432),
            'database' => env('SP_DB_DATABASE', 'forge'),
            'username' => env('SP_DB_USERNAME', 'forge'),
            'password' => env('SP_DB_PASSWORD', ''),
            'charset' => env('SP_DB_CHARSET', 'utf8'),
            'prefix' => env('SP_DB_PREFIX', ''),
            'search_path' => env('SP_DB_SCHEMA', 'public'),
            'sslmode' => env('SP_DB_SSL_MODE', 'prefer'),
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'host' => env('SP_DB_HOST', 'localhost'),
            'port' => env('SP_DB_PORT', 1433),
            'database' => env('SP_DB_DATABASE', 'forge'),
            'username' => env('SP_DB_USERNAME', 'forge'),
            'password' => env('SP_DB_PASSWORD', ''),
            'charset' => env('SP_DB_CHARSET', 'utf8'),
            'prefix' => env('SP_DB_PREFIX', ''),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer set of commands than a typical key-value systems
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'client' => env('SP_APP_REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('SP_APP_REDIS_CLUSTER', 'redis'),
            'prefix' => env('SP_APP_REDIS_PREFIX', Str::slug(env('APP_NAME', 'lumen'), '_').'_database_'),
        ],

        'default' => [
            'url' => env('SP_APP_REDIS_URL'),
            'host' => env('SP_APP_REDIS_HOST', '127.0.0.1'),
            'password' => env('SP_APP_REDIS_PASSWORD'),
            'port' => env('SP_APP_REDIS_PORT', '6379'),
            'database' => env('SP_APP_REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('SP_APP_REDIS_URL'),
            'host' => env('SP_APP_REDIS_HOST', '127.0.0.1'),
            'password' => env('SP_APP_REDIS_PASSWORD'),
            'port' => env('SP_APP_REDIS_PORT', '6379'),
            'database' => env('SP_APP_REDIS_CACHE_DB', '1'),
        ],

    ],

];
