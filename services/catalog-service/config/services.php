<?php

return [
    'rabbitmq' => [
        'url' => env('RABBITMQ_URL', 'amqp://guest:guest@localhost:5672'),
    ],

    'elasticsearch' => [
        'scheme'   => env('ELASTICSEARCH_SCHEME', 'http'),
        'host'     => env('ELASTICSEARCH_HOST', 'localhost'),
        'port'     => env('ELASTICSEARCH_PORT', 9200),
        'user'     => env('ELASTICSEARCH_USER', ''),
        'password' => env('ELASTICSEARCH_PASSWORD', ''),
        'index'    => [
            'products' => env('ELASTICSEARCH_INDEX_PRODUCTS', 'products'),
        ],
    ],
];