<?php
return [

    'name' => 'Rendszer',

    'db_prefix' => ['admin_'],

    'version' => '2.0.0',

    'perms' => [
        // 'badmin' => 'Admin',
    ],

    'menu' => [
        'useredit' => [
            'icon' => 'f007',
            'name' => 'Felhasználó kezelés',
        ],
        'groupedit' => [
            'icon' => 'f5fd',
            'name' => 'Szerepkör kezelés',
        ],
    ]
];
