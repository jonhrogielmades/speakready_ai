<?php

use Illuminate\Support\Str;

$databaseUrl = env('DATABASE_URL');
$databaseUrlScheme = is_string($databaseUrl) ? parse_url($databaseUrl, PHP_URL_SCHEME) : null;
$databaseUrlHost = is_string($databaseUrl) ? parse_url($databaseUrl, PHP_URL_HOST) : null;
$defaultDatabaseConnection = match ($databaseUrlScheme) {
    'postgres', 'postgresql', 'pgsql' => 'pgsql',
    'mysql', 'mysql2', 'mariadb' => 'mysql',
    'sqlite', 'sqlite3' => 'sqlite',
    'sqlsrv', 'mssql' => 'sqlsrv',
    default => 'mysql',
};
$postgresHost = env('DB_HOST', '127.0.0.1');
$postgresHostWasPartialRenderHost = is_string($postgresHost) && preg_match('/^dpg-[^.]+$/', $postgresHost);

if ($postgresHostWasPartialRenderHost) {
    $postgresRegion = env('RENDER_POSTGRES_REGION', 'singapore');
    $postgresHost = "{$postgresHost}.{$postgresRegion}-postgres.render.com";
}

if (is_string($databaseUrlHost) && preg_match('/^dpg-[^.]+$/', $databaseUrlHost)) {
    $postgresRegion = env('RENDER_POSTGRES_REGION', 'singapore');
    $expandedDatabaseUrlHost = "{$databaseUrlHost}.{$postgresRegion}-postgres.render.com";
    $databaseUrl = preg_replace(
        '/(^[a-z][a-z0-9+.-]*:\/\/(?:[^@\/?#]*@)?)'.preg_quote($databaseUrlHost, '/').'(?=[:\/?#]|$)/i',
        '${1}'.$expandedDatabaseUrlHost,
        $databaseUrl,
        1
    );
    $postgresHostWasPartialRenderHost = true;
}

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

    'default' => env('DB_CONNECTION', $defaultDatabaseConnection),

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
            'url' => $databaseUrl,
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => $databaseUrl,
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => $databaseUrl,
            'host' => $postgresHost,
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => env('DB_SSLMODE', $postgresHostWasPartialRenderHost ? 'require' : 'prefer'),
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => $databaseUrl,
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            // 'encrypt' => env('DB_ENCRYPT', 'yes'),
            // 'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'false'),
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
    | provides a richer body of commands than a typical key-value system
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];
