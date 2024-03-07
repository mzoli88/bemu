<?php

$menu = [];

if (defined('BORDER_EMU')){

    $menu['useredit'] = [
        'icon' => 'f007',
        'name' => 'Felhasználó kezelés',
    ];
    
    $menu['groupedit'] = [
        'icon' => 'f5fd',
        'name' => 'Szerepkör kezelés',
    ];

    $menu['modules'] = [
        'icon' => 'f14e',
        'name' => 'Modulok',
    ];
}

$menu['systemparam'] = [
    'icon' => 'f7d9',
    'name' => 'Rendszer beállítások',
];


return [

    'name' => 'Rendszer',

    'db_prefix' => ['admin_'],

    'version' => '2.0.0',

    'perms' => [
        // 'badmin' => 'Admin',
    ],

    'menu' => $menu

];
