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

    'default' => env('DB_CONNECTION', 'mysql'),

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

        'mysql' => [
            'driver'    => 'mysql',
            'url'       => env('DATABASE_URL'),
            'host'      => env('DB_HOST', '127.0.0.1'),
            'port'      => env('DB_PORT', '3306'),
            'database'  => env('DB_DATABASE', 'market'),
            'username'  => env('DB_USERNAME', 'root'),
            'password'  => env('DB_PASSWORD', 'toor'),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'prefix_indexes' => true,
            'strict'    => true,
            'engine'    => null,
            'options'   => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],


        'mysql_pro' => [
            'driver'    => 'mysql',
            'url'       => env('DATABASE_URL'),
            'host'      => env('DB_HOST_PRO', '127.0.0.1'),
            'port'      => env('DB_PORT_PRO', '3306'),
            'database'  => env('DB_DATABASE_PRO', 'market'),
            'username'  => env('DB_USERNAME_PRO', 'root'),
            'password'  => env('DB_PASSWORD_PRO', 'toor'),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'prefix_indexes' => true,
            'strict'    => true,
            'engine'    => null,
            'options'   => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],


        // panel.idiomund.com


        'mysql_idiomund' => [
            'driver'    => 'mysql',
            'host'      => env('SIL_DB_PANEL_HOST', ''),
            'database'  => 'tienda',
            'username'  => env('SIL_DB_PANEL_USERNAME', ''),
            'password'  => env('SIL_DB_PANEL_PASSWORD', ''),
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
            'strict'    => false,
        ],


        'mysql_idiomund_importadores' => [
            'driver'    => 'mysql',
            'host'      => env('SIL_DB_PANEL_HOST', ''),
            'database'  => 'importadores',
            'username'  => env('SIL_DB_PANEL_USERNAME', ''),
            'password'  => env('SIL_DB_PANEL_PASSWORD', ''),
            'charset'   => 'utf8',
            'collation' => 'utf8_general_ci',
            'prefix'    => '',
            'strict'    => false,
        ],


        // panel57.idiomund.com


        'mysql_idiomund_panel57' => [
            'driver'    => 'mysql',
            'host'      => env('SIL_DB_PANEL57_HOST', '127.0.0.1'),
            'port'      => env('DB_PORT', '3306'),
            'database'  => 'importadores',
            'username'  => env('SIL_DB_PANEL57_USERNAME', 'root'),
            'password'  => env('SIL_DB_PANEL57_PASSWORD', 'toor'),
            'charset'   => 'utf8',
            'collation' => 'utf8_general_ci',
            'prefix'    => '',
            'strict'    => false,
        ],

        'prestashop_master' => [
            'driver'    => 'mysql',
            'host'      => env('SIL_DB_PANEL57_HOST', '127.0.0.1'),
            'port'      => env('DB_PORT', '3306'),
            'database'  => 'prestashop_master',
            'username'  => env('SIL_DB_PANEL57_USERNAME', 'root'),
            'password'  => env('SIL_DB_PANEL57_PASSWORD', 'toor'),
            'charset'   => 'utf8',
            'collation' => 'utf8_general_ci',
            'prefix'    => '',
            'strict'    => false,
        ],

        'prestashop_pro' => [
            'driver'    => 'mysql',
            'host'      => env('SIL_DB_PANEL57_HOST', '127.0.0.1'),
            'port'      => env('DB_PORT', '3306'),
            'database'  => 'prestashop_pro',
            'username'  => env('SIL_DB_PANEL57_USERNAME', 'root'),
            'password'  => env('SIL_DB_PANEL57_PASSWORD', 'toor'),
            'charset'   => 'utf8',
            'collation' => 'utf8_general_ci',
            'prefix'    => '',
            'strict'    => false,
        ],

        'prestashop_electro' => [
            'driver'    => 'mysql',
            'host'      => env('SIL_DB_PANEL57_HOST', '127.0.0.1'),
            'port'      => env('DB_PORT', '3306'),
            'database'  =>  'prestashop_electro',
            'username'  => env('SIL_DB_PANEL57_USERNAME', 'root'),
            'password'  => env('SIL_DB_PANEL57_PASSWORD', 'toor'),
            'charset'   => 'utf8',
            'collation' => 'utf8_general_ci',
            'prefix'    => '',
            'strict'    => false,
        ],

        'prestashop_mediamarkt' => [
            'driver'    => 'mysql',
            'host'      => env('SIL_DB_PANEL57_HOST', '127.0.0.1'),
            'port'      => env('DB_PORT', '3306'),
            'database'  => 'prestashop_mediamarkt',
            'username'  => env('SIL_DB_PANEL57_USERNAME', 'root'),
            'password'  => env('SIL_DB_PANEL57_PASSWORD', 'toor'),
            'charset'   => 'utf8',
            'collation' => 'utf8_general_ci',
            'prefix'    => '',
            'strict'    => false,
        ],

        'prestashop_thehpshop' => [
            'driver'    => 'mysql',
            'host'      => env('SIL_DB_PANEL57_HOST', '127.0.0.1'),
            'port'      => env('DB_PORT', '3306'),
            'database'  => 'prestashop_thehpshop',
            'username'  => env('SIL_DB_PANEL57_USERNAME', 'root'),
            'password'  => env('SIL_DB_PANEL57_PASSWORD', 'toor'),
            'charset'   => 'utf8',
            'collation' => 'utf8_general_ci',
            'prefix'    => '',
            'strict'    => false,
        ],

        'prestashop_pceducacion' => [
            'driver'    => 'mysql',
            'host'      => env('SIL_DB_PANEL57_HOST', '127.0.0.1'),
            'port'      => env('DB_PORT', '3306'),
            'database'  => 'prestashop_pceducacion',
            'username'  => env('SIL_DB_PANEL57_USERNAME', 'root'),
            'password'  => env('SIL_DB_PANEL57_PASSWORD', 'toor'),
            'charset'   => 'utf8',
            'collation' => 'utf8_general_ci',
            'prefix'    => '',
        ],

        'prestashop_aep' => [
            'driver'    => 'mysql',
            'host'      => env('SIL_DB_PANEL57_HOST', '127.0.0.1'),
            'port'      => env('DB_PORT', '3306'),
            'database'  => 'prestashop_aliexpress',
            'username'  => env('SIL_DB_PANEL57_USERNAME', 'root'),
            'password'  => env('SIL_DB_PANEL57_PASSWORD', 'toor'),
            'charset'   => 'utf8',
            'collation' => 'utf8_general_ci',
            'prefix'    => '',
        ],

        'prestashop_udg' => [
            'driver'    => 'mysql',
            'host'      => env('SIL_DB_PANEL57_HOST', '127.0.0.1'),
            'port'      => env('DB_PORT', '3306'),
            'database'  => 'prestashop_udg',
            'username'  => env('SIL_DB_PANEL57_USERNAME', 'root'),
            'password'  => env('SIL_DB_PANEL57_PASSWORD', 'toor'),
            'charset'   => 'utf8',
            'collation' => 'utf8_general_ci',
            'prefix'    => '',
        ],




        /* 'sqlite' => [
            'driver'    => 'sqlite',
            'url'       => env('DATABASE_URL'),
            'database'  => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix'    => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        'pgsql' => [
            'driver'    => 'pgsql',
            'url'       => env('DATABASE_URL'),
            'host'      => env('DB_HOST', '127.0.0.1'),
            'port'      => env('DB_PORT', '5432'),
            'database'  => env('DB_DATABASE', 'forge'),
            'username'  => env('DB_USERNAME', 'forge'),
            'password'  => env('DB_PASSWORD', ''),
            'charset'   => 'utf8',
            'prefix'    => '',
            'prefix_indexes' => true,
            'schema'    => 'public',
            'sslmode'   => 'prefer',
        ],

        'sqlsrv' => [
            'driver'    => 'sqlsrv',
            'url'       => env('DATABASE_URL'),
            'host'      => env('DB_HOST', 'localhost'),
            'port'      => env('DB_PORT', '1433'),
            'database'  => env('DB_DATABASE', 'forge'),
            'username'  => env('DB_USERNAME', 'forge'),
            'password'  => env('DB_PASSWORD', ''),
            'charset'   => 'utf8',
            'prefix'    => '',
            'prefix_indexes' => true,
        ], */

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
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'Marketplace Specialist'), '_').'_database_'),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_DB', 0),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_CACHE_DB', 1),
        ],

    ],

];
