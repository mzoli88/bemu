<?php

$menu = [];

if (defined('BORDER_EMU')){

    $menu['useredit'] = [
        'icon' => 'f007',
        'name' => 'Felhasználó kezelés',
        'noprem' => true,
    ];
    
    $menu['groupedit'] = [
        'icon' => 'f5fd',
        'name' => 'Szerepkör kezelés',
        'noprem' => true,
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
