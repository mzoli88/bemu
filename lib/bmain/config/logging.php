<?php

use App\Logging\CustomLogger;

return [

    'default' => 'default',

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => false,
    ],

    'channels' => [
        "default" => [
            'driver' => 'custom',
            'via' => CustomLogger::class
        ], 
        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],
    ],

];
