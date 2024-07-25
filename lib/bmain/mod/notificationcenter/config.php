<?php
return [

    'name' => 'Értesítés központ',
    'icon' => 'f674',

    'version' => '1.1.0',

    'db_prefix' => ['notify_'],

    'perms' => [
        'cnadmin' => 'Értesítés központ karbantartás',
    ],

    'permsInfo' => [
        'cnadmin' => 'Értesítés központ karbantartás',
    ],

    'menu' => [
        'notification' => [
            'icon' => 'f086',
            'name' => 'Értesítések',
        ],
        'notificationmonitor' => [
            'icon' => 'f26c',
            'name' => 'Kiküldés monitorozás',
        ],
        'notificationparam' => [
            'icon' => 'f0ad',
            'name' => 'Paraméterek',
        ],
    ]
];
