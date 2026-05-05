<?php

return [
    'rabbitmq' => [
        'url' => env('RABBITMQ_URL', 'amqp://guest:guest@localhost:5672'),
    ],

    'catalog' => [
        'url' => env('CATALOG_SERVICE_URL', 'http://catalog-service:8000'),
    ],
];
