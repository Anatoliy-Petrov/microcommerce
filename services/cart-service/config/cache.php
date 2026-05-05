<?php

return [
    'default' => env('CACHE_DRIVER', 'redis'),

    'stores' => [
        'array' => ['driver' => 'array', 'serialize' => false],
        'redis' => [
            'driver'     => 'redis',
            'connection' => 'cache',
        ],
    ],

    'prefix' => 'cart_service_cache',
];
