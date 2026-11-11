<?php

declare(strict_types=1);

return [
    'base_url' => env('SKIR_CLIENT_BASE_URL'),
    'endpoint' => env('SKIR_CLIENT_ENDPOINT', '/'),
    'format' => env('SKIR_CLIENT_FORMAT', 'dense'),

    'auth' => [
        // null (no authentication), header, bearer, or basic
        'type' => env('SKIR_CLIENT_AUTH_TYPE'),
        'header' => env('SKIR_CLIENT_AUTH_HEADER', 'Authorization'),
        'token' => env('SKIR_CLIENT_AUTH_TOKEN'),
        'username' => env('SKIR_CLIENT_AUTH_USERNAME'),
        'password' => env('SKIR_CLIENT_AUTH_PASSWORD'),
    ],

    'generator' => [
        'node' => env('SKIR_CLIENT_NODE', 'node'),
        'skir_bin' => env('SKIR_CLIENT_SKIR_BIN', base_path('node_modules/skir/dist/compiler.js')),
        'root' => env('SKIR_CLIENT_ROOT', base_path()),
    ],
];
