<?php

return [

    'installed' => env('MONETA_INSTALLED'),

    'flag_path' => env('MONETA_INSTALLED_FLAG'),

    'php_version' => '8.3.0',

    'extensions' => [
        'ctype',
        'curl',
        'dom',
        'fileinfo',
        'filter',
        'hash',
        'mbstring',
        'openssl',
        'session',
        'tokenizer',
        'xml',
    ],

    'drivers' => [
        'sqlite' => 'pdo_sqlite',
        'mysql' => 'pdo_mysql',
        'mariadb' => 'pdo_mysql',
        'pgsql' => 'pdo_pgsql',
        'sqlsrv' => 'pdo_sqlsrv',
    ],

];
