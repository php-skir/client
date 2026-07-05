<?php

declare(strict_types=1);

return [
    'base_url' => env('SKIR_CLIENT_BASE_URL'),
    'endpoint' => env('SKIR_CLIENT_ENDPOINT', '/'),
    'codec' => env('SKIR_CLIENT_CODEC', 'dense_json'),

    'generator' => [
        'node' => env('SKIR_CLIENT_NODE', 'node'),
        'skir_bin' => env('SKIR_CLIENT_SKIR_BIN', base_path('node_modules/skir/dist/compiler.js')),
        'root' => env('SKIR_CLIENT_ROOT', base_path()),
    ],
];
