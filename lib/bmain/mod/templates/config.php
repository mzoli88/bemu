<?php
return [

    'name' => 'Sablonkezelő',
    'icon' => 'f1c9',
    'sort' => 990,

    'version' => '1.0.0',

    'db_prefix' => ['template_'],

    'perms' => [
        'edit_events' => 'Események karbantartása'
    ],

    'menu' => [
        'messages' => [
            'icon' => 'f0f3',
            'name' => 'Események',
        ],
        // 'email' => [
        //     'icon' => 'f0e0',
        //     'name' => 'Email sablonok',
        // ],
        // 'pdf' => [
        //     'icon' => 'f1c1',
        //     'name' => 'PDF sablonok',
        // ],
    ]
];
