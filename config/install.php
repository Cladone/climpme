<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Server Requirements
    |--------------------------------------------------------------------------
    */
    'php_version' => '8.2.0',

    'extensions' => [
        'php' => [
            'BCMath',
            'Ctype',
            'cURL',
            'DOM',
            'Exif',
            'Fileinfo',
            'Filter',
            'GD',
            'Hash',
            'Intl',
            'JSON',
            'Mbstring',
            'OpenSSL',
            'PCRE',
            'PDO',
            'Session',
            'Sodium',
            'Tokenizer',
            'XML',
        ],
        'apache' => [
            'mod_rewrite',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File permissions
    |--------------------------------------------------------------------------
    */
    'permissions' => [
        'Files' => [
            '.env',
        ],
        'Folders' =>
        [
            'bootstrap/cache',
            'lang',
            'public/uploads/brand',
            'public/uploads/users',
            'storage',
            'storage/framework',
            'storage/framework/cache',
            'storage/framework/cache/data',
            'storage/framework/sessions',
            'storage/framework/views',
            'storage/logs',
        ],
    ]
];
