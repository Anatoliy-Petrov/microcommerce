<?php

return [
    'driver' => env('SCOUT_DRIVER', 'elasticsearch'),
    'prefix' => env('SCOUT_PREFIX', ''),
    'queue'  => env('SCOUT_QUEUE', false),
    'chunk'  => ['searchable' => 500, 'unsearchable' => 500],
    'soft_delete' => false,
];